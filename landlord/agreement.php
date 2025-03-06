<?php
session_name("landlord_session");
session_start();
include '../db_connection.php'; // Database connection
include 'landlord_sidebar.php'; // Sidebar

// Ensure landlord is logged in
if (!isset($_SESSION['landlord_id'])) {
  header("Location: ../landlord_login.php");
  exit();
}

$landlord_id = $_SESSION['landlord_id'];

// Fetch agreement details for view section
$query_agreements = "
  SELECT 
      ta.booked_house_id, 
      lh.house_number, 
      ta.tenancy_period, 
      ta.start_date, 
      ta.monthly_rent, 
      ta.deposit, 
      t.tenant_full_name AS leader_name, 
      GROUP_CONCAT(tm.tenant_full_name SEPARATOR ', ') AS members_list
  FROM 
      tenancy_agreements ta
  INNER JOIN 
      house_booking hb ON ta.booked_house_id = hb.booked_house_id
  INNER JOIN 
      landlord_house lh ON hb.house_id = lh.house_id
  INNER JOIN 
      housemate_role hr_leader ON hr_leader.housemate_id = hb.housemate_id AND hr_leader.house_role = 'leader'
  INNER JOIN 
      tenant t ON hr_leader.tenant_id = t.tenant_id
  LEFT JOIN 
      house_group hg ON hg.booked_house_id = hb.booked_house_id
  LEFT JOIN 
      housemate_role hr_member ON hr_member.housemate_id = hg.housemate_id AND hr_member.house_role = 'member'
  LEFT JOIN 
      tenant tm ON hr_member.tenant_id = tm.tenant_id
  WHERE 
      ta.landlord_id = ?
  GROUP BY ta.booked_house_id";

$stmt_agreements = $conn->prepare($query_agreements);
$stmt_agreements->bind_param("i", $landlord_id);
$stmt_agreements->execute();
$result_agreements = $stmt_agreements->get_result();

$viewable_agreements = [];
while ($row = $result_agreements->fetch_assoc()) {
  $viewable_agreements[] = $row;
}
$stmt_agreements->close();


// Fetch existing tenancy agreements
$existing_agreements = [];
$query_existing = "SELECT booked_house_id, tenancy_period, start_date FROM tenancy_agreements WHERE landlord_id = ?";
$stmt_existing = $conn->prepare($query_existing);
$stmt_existing->bind_param("i", $landlord_id);
$stmt_existing->execute();
$result_existing = $stmt_existing->get_result();

while ($row = $result_existing->fetch_assoc()) {
  $existing_agreements[$row['booked_house_id']] = [
    'tenancy_period' => $row['tenancy_period'],
    'start_date' => $row['start_date']
  ];
}
$stmt_existing->close();

// Fetch landlord's full name
$query_landlord = "SELECT landlord_full_name FROM landlord WHERE landlord_id = ?";
$stmt_landlord = $conn->prepare($query_landlord);
$stmt_landlord->bind_param("i", $landlord_id);
$stmt_landlord->execute();
$result_landlord = $stmt_landlord->get_result();
$landlord_data = $result_landlord->fetch_assoc();
$landlord_full_name = $landlord_data['landlord_full_name'];
$stmt_landlord->close();

// Fetch accepted house bookings
$query_bookings = "
  SELECT 
      hb.booked_house_id, 
      lh.house_number, 
      t_leader.tenant_full_name AS leader_name,
      t_leader.tenant_id AS leader_id,
      lh.monthly_rental, 
      lh.deposit,
      GROUP_CONCAT(t_member.tenant_full_name SEPARATOR ', ') AS members_list
  FROM 
      house_booking hb
  INNER JOIN 
      landlord_house lh ON hb.house_id = lh.house_id
  INNER JOIN 
      housemate_role hr_leader ON hr_leader.housemate_id = hb.housemate_id AND hr_leader.house_role = 'leader'
  INNER JOIN 
      tenant t_leader ON hr_leader.tenant_id = t_leader.tenant_id
  LEFT JOIN 
      house_group hg ON hg.booked_house_id = hb.booked_house_id
  LEFT JOIN 
      housemate_role hr_member ON hr_member.housemate_id = hg.housemate_id AND hr_member.house_role = 'member'
  LEFT JOIN 
      tenant t_member ON hr_member.tenant_id = t_member.tenant_id
  WHERE 
      lh.landlord_id = ? AND hb.booking_status = 'approved'
  GROUP BY hb.booked_house_id";
$stmt_bookings = $conn->prepare($query_bookings);
$stmt_bookings->bind_param("i", $landlord_id);
$stmt_bookings->execute();
$result_bookings = $stmt_bookings->get_result();

$accepted_bookings = [];
while ($row = $result_bookings->fetch_assoc()) {
  $row['is_agreement_exists'] = array_key_exists($row['booked_house_id'], $existing_agreements);
  if ($row['is_agreement_exists']) {
    $row['tenancy_period'] = $existing_agreements[$row['booked_house_id']]['tenancy_period'];
    $row['start_date'] = $existing_agreements[$row['booked_house_id']]['start_date'];
  }
  $accepted_bookings[] = $row;
}
$stmt_bookings->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tenancy Agreement - Stay1B</title>
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/landlord.css">
</head>



<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script>
function populateAgreementForm(selectElement) {
    const bookingData = selectElement.value ? JSON.parse(selectElement.value) : null;

    if (bookingData) {
        document.getElementById('tenancyAgreementForm').style.display = 'block';
        document.getElementById('houseNumber').value = bookingData.house_number;
        document.getElementById('leaderName').value = bookingData.leader_name;
        document.getElementById('membersList').value = bookingData.members_list || "No members available";
        document.getElementById('monthlyRent').value = bookingData.monthly_rental;
        document.getElementById('deposit').value = bookingData.deposit;

        if (bookingData.is_agreement_exists) {
            document.getElementById('tenancyPeriod').value = bookingData.tenancy_period || '';
            document.getElementById('startDate').value = bookingData.start_date || '';
            lockFormFields();
            document.getElementById('generateButton').disabled = true;
        } else {
            unlockFormFields();
            document.getElementById('generateButton').disabled = false;
        }
    } else {
        document.getElementById('tenancyAgreementForm').style.display = 'none';
    }
}

function validateAndGenerate() {
    const bookingSelect = document.getElementById('bookingSelect');
    const tenancyPeriod = document.getElementById('tenancyPeriod').value;
    const startDate = document.getElementById('startDate').value;
    const electronicSignature = document.getElementById('electronicSignature');

    if (!bookingSelect.value) {
        alert('Please select a booking from the list.');
        return;
    }

    if (!tenancyPeriod || !startDate) {
        alert('Please fill in all required fields.');
        return;
    }

    if (!electronicSignature.checked) {
        alert('You must agree to the terms.');
        return;
    }

    saveAgreementToDatabase(bookingSelect.value, tenancyPeriod, startDate).then(() => {
        generateAgreementPDF();
        lockFormFields();

        
    });
}

function lockFormFields() {
    const formElements = document.querySelectorAll('#tenancyAgreementForm input, #tenancyAgreementForm select, #tenancyAgreementForm textarea');
    formElements.forEach(element => {
        if (element.id !== 'electronicSignature') {
            element.setAttribute('disabled', 'true');
        }
    });
}

function unlockFormFields() {
    const formElements = document.querySelectorAll('#tenancyAgreementForm input, #tenancyAgreementForm select, #tenancyAgreementForm textarea');
    formElements.forEach(element => {
        if (!['houseNumber', 'leaderName', 'membersList'].includes(element.id)) {
            element.removeAttribute('disabled');
        }
    });
}

function saveAgreementToDatabase(bookingData, tenancyPeriod, startDate) {
    const parsedData = JSON.parse(bookingData);

    const requestData = {
        booked_house_id: parsedData.booked_house_id,
        landlord_id: <?= $landlord_id ?>,
        tenant_id: parsedData.leader_id,
        tenancy_period: tenancyPeriod,
        start_date: startDate,
        monthly_rent: parsedData.monthly_rental,
        deposit: parsedData.deposit
    };

    return fetch('save_agreement.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData),
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Failed to save agreement: ' + data.message);
            throw new Error(data.message);
        }
        alert('Agreement successfully saved.');
    });
}
</script>

<body>
  <div class="dashboard-container">
    <main>
      <header>
        <h1>Tenancy Agreement</h1>
      </header>

      <section class="view-agreements">
      <h2>All Generated Tenancy Agreements</h2>
  <?php if (count($viewable_agreements) > 0): ?>
    <table class="agreements-table">
      <thead>
        <tr>
          <th>House Number</th>
          <th>House Leader</th>
          <th>House Members</th>
          <th>Start Date</th>
          <th>End Date</th> <!-- End Date column added -->
          <th>Monthly Rent</th>
          <th>Deposit</th>
          <th>Download PDF</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($viewable_agreements as $agreement): 
          // Calculate End Date
          $startDate = new DateTime($agreement['start_date']);
          $tenancyPeriod = strtolower($agreement['tenancy_period']);
          
          // Add months based on tenancy period
          switch ($tenancyPeriod) {
              case '6 months':
                  $startDate->modify('+6 months');
                  break;
              case '1 year':
                  $startDate->modify('+12 months');
                  break;
              case '1 year 6 months':
                  $startDate->modify('+18 months');
                  break;
              case '2 years':
                  $startDate->modify('+24 months');
                  break;
              default:
                  $startDate = null; // Invalid or unspecified period
          }
          $endDate = $startDate ? $startDate->format('d-m-Y') : '';
        ?>
          <tr>
            <td><?= htmlspecialchars($agreement['house_number']) ?></td>
            <td><?= htmlspecialchars($agreement['leader_name']) ?></td>
            <td><?= htmlspecialchars($agreement['members_list'] ?: "No members available") ?></td>
            <td><?= htmlspecialchars(date("d-m-Y", strtotime($agreement['start_date']))) ?></td>
            <td><?= htmlspecialchars($endDate) ?></td> <!-- Display calculated End Date -->
            <td>RM <?= htmlspecialchars(number_format($agreement['monthly_rent'], 2)) ?></td> <!-- Add RM -->
            <td>RM <?= htmlspecialchars(number_format($agreement['deposit'], 2)) ?></td> <!-- Add RM -->
            <td>
              <button type="button" class="download-pdf-text-button" 
                onclick="generateAgreementPDF(<?= htmlspecialchars(json_encode($agreement)) ?>)">
                Download PDF
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No agreements available for viewing.</p>
  <?php endif; ?>
</section>

      <section class="agreement-form">
      <h2>Generate Tenancy Agreement</h2>
        <?php if (count($accepted_bookings) > 0): ?>
          <form id="agreementSelectionForm">
            <label for="bookingSelect">Select a Booking:</label>
            <select id="bookingSelect" name="bookingSelect" required onchange="populateAgreementForm(this)">
              <option value="">-- Select a Booking --</option>
              <?php foreach ($accepted_bookings as $booking): ?>
                <option value="<?= htmlspecialchars(json_encode($booking)) ?>">
                  <?= htmlspecialchars("House: {$booking['house_number']} - Leader: {$booking['leader_name']}") ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        <?php else: ?>
          <p>No accepted bookings available.</p>
        <?php endif; ?>

        <form id="tenancyAgreementForm" style="display: none;">
          <h2>Tenancy Agreement Details</h2>

          <label for="houseNumber">House Number:</label>
          <input type="text" id="houseNumber" name="houseNumber" readonly required>

          <label for="landlordName">Landlord Name:</label>
          <input type="text" id="landlordName" name="landlordName" readonly value="<?= htmlspecialchars($landlord_full_name) ?>" required>

          <label for="leaderName">House Leader Name:</label>
          <input type="text" id="leaderName" name="leaderName" readonly required>

          <label for="membersList">House Members:</label>
          <textarea id="membersList" name="membersList" readonly></textarea>

          <label for="tenancyPeriod">Tenancy Period:</label>
          <select id="tenancyPeriod" name="tenancyPeriod" required>
            <option value="">Select tenancy duration</option>
            <option value="6 months">6 months</option>
            <option value="1 year">1 year</option>
            <option value="1 year 6 months">1 year 6 months</option>
            <option value="2 years">2 years</option>
          </select>

          <label for="startDate">Start Date:</label>
          <input type="date" id="startDate" name="startDate" required>

          <label for="monthlyRent">Monthly Rental (RM):</label>
          <input type="number" id="monthlyRent" name="monthlyRent" readonly required>

          <label for="deposit">Deposit Amount (RM):</label>
          <input type="number" id="deposit" name="deposit" readonly required>

          <div class="agreement-checkbox">
            <label for="electronicSignature">
              <input type="checkbox" id="electronicSignature" name="electronicSignature" required>
              I agree this is a computer-generated agreement and does not require a physical signature.
            </label>
          </div>

          <button type="button" class="action-button" onclick="validateAndGenerate()" id="generateButton">Generate Agreement</button>
        </form>
      </section>

<script>
function generateAgreementPDF(agreement) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const houseNumber = agreement.house_number;
    const landlordName = "<?= htmlspecialchars($landlord_full_name) ?>";
    const leaderName = agreement.leader_name;
    const membersList = agreement.members_list || "No members available";
    const tenancyPeriod = agreement.tenancy_period;
    const startDate = agreement.start_date.split("-").reverse().join("-");
    const monthlyRent = parseFloat(agreement.monthly_rent).toFixed(2);
    const deposit = parseFloat(agreement.deposit).toFixed(2);

    // Define text wrapping width
    const pageWidth = 190; // Total width of the page (210) minus margins (10 * 2)
    const textMargin = 20; // Left margin for text alignment

    // Add page border
    doc.setLineWidth(0.5);
    doc.rect(10, 10, 190, 277); // x, y, width, height

    // Title
    doc.setFontSize(18);
    doc.setFont("Helvetica", "bold");
    doc.text("TENANCY AGREEMENT", 105, 25, { align: "center" });

    // Agreement Introduction
    doc.setFontSize(12);
    doc.setFont("Helvetica", "normal");
    const introText = "This Tenancy Agreement is made and entered into on the date specified below:";
    doc.text(doc.splitTextToSize(introText, pageWidth - 2 * textMargin), textMargin, 40);
    doc.text(`Start Date: ${startDate}`, textMargin, 50);

    // Landlord Section
    doc.setFont("Helvetica", "bold");
    doc.text("BETWEEN:", textMargin, 60);
    doc.setFont("Helvetica", "normal");
    const landlordText = `The Landlord: ${landlordName}, who agrees to lease the property to the House Leader and House Members specified below.`;
    doc.text(doc.splitTextToSize(landlordText, pageWidth - 2 * textMargin), textMargin + 10, 70);

    // Leader and Members Section
    doc.setFont("Helvetica", "bold");
    doc.text("AND:", textMargin, 90);
    doc.setFont("Helvetica", "normal");
    const leaderText = `The House Leader: ${leaderName}, along with the following House Members:`;
    doc.text(doc.splitTextToSize(leaderText, pageWidth - 2 * textMargin), textMargin + 10, 100);
    doc.text(doc.splitTextToSize(membersList, pageWidth - 2 * textMargin), textMargin + 20, 110);

    // Agreement Details
    doc.setFont("Helvetica", "bold");
    doc.text("NOW, IT IS AGREED AS FOLLOWS:", textMargin, 130);
    doc.setFont("Helvetica", "normal");
    const propertyDetails = `1. Property Details: House Number: ${houseNumber}`;
    doc.text(doc.splitTextToSize(propertyDetails, pageWidth - 2 * textMargin), textMargin + 10, 140);
    doc.text(`2. Tenancy Period: ${tenancyPeriod}`, textMargin + 10, 150);
    doc.text(`3. Monthly Rental: RM ${monthlyRent}`, textMargin + 10, 160);
    const depositText = `4. Security Deposit: RM ${deposit}. The security deposit will be held for the duration of the tenancy and will be refundable subject to the terms and conditions of this agreement.`;
    doc.text(doc.splitTextToSize(depositText, pageWidth - 2 * textMargin), textMargin + 10, 170);

    // Footer Section
    doc.setFontSize(10);
    doc.setFont("Helvetica", "italic");
    doc.text(
        "This is a computer-generated agreement. No physical signature is required.",
        105,
        285,
        { align: "center" }
    );

    // Save the PDF
    doc.save(`Agreement_House_${houseNumber}.pdf`);
}
</script>


    </main>
  </div>


