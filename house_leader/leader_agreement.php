<?php
session_name("house_leader_session");
session_start();
include '../db_connection.php';
include 'leader_sidebar.php';

if (!isset($_SESSION['housemate_id'])) {
    header("Location: leader_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id'];

// Fetch agreement details for the house leader
$query_agreement = "
    SELECT 
        ta.booked_house_id,
        lh.house_number,
        ta.tenancy_period,
        ta.start_date,
        ta.monthly_rent,
        ta.deposit,
        l.landlord_full_name AS landlord_name,
        t_leader.tenant_full_name AS leader_name,
        GROUP_CONCAT(DISTINCT tm.tenant_full_name SEPARATOR ', ') AS members_list
    FROM 
        tenancy_agreements ta
    INNER JOIN 
        house_booking hb ON ta.booked_house_id = hb.booked_house_id
    INNER JOIN 
        landlord_house lh ON hb.house_id = lh.house_id
    INNER JOIN 
        landlord l ON lh.landlord_id = l.landlord_id
    INNER JOIN 
        housemate_role hr_leader ON hr_leader.housemate_id = hb.housemate_id AND hr_leader.house_role = 'leader'
    INNER JOIN 
        tenant t_leader ON hr_leader.tenant_id = t_leader.tenant_id
    LEFT JOIN 
        housemate_application ha ON ha.booked_house_id = hb.booked_house_id
    LEFT JOIN 
        housemate_role hr_member ON hr_member.housemate_id = ha.housemate_id
    LEFT JOIN 
        tenant tm ON hr_member.tenant_id = tm.tenant_id
    WHERE 
        hr_leader.housemate_id = ?
    GROUP BY 
        ta.booked_house_id";

$stmt_agreement = $conn->prepare($query_agreement);
$stmt_agreement->bind_param("i", $housemate_id);
$stmt_agreement->execute();
$result_agreement = $stmt_agreement->get_result();

$agreement_details = $result_agreement->fetch_assoc();
$stmt_agreement->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenancy Agreement - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script>
    function generateAgreementPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const houseNumber = "<?= htmlspecialchars($agreement_details['house_number'] ?? '') ?>";
        const landlordName = "<?= htmlspecialchars($agreement_details['landlord_name'] ?? '') ?>";
        const leaderName = "<?= htmlspecialchars($agreement_details['leader_name'] ?? '') ?>";
        const membersList = "<?= htmlspecialchars($agreement_details['members_list'] ?? 'No members available') ?>";
        const tenancyPeriod = "<?= htmlspecialchars($agreement_details['tenancy_period'] ?? '') ?>";
        const startDate = "<?= htmlspecialchars(date('d-m-Y', strtotime($agreement_details['start_date'] ?? ''))) ?>";
        const monthlyRent = parseFloat("<?= htmlspecialchars($agreement_details['monthly_rent'] ?? 0) ?>").toFixed(2);
        const deposit = parseFloat("<?= htmlspecialchars($agreement_details['deposit'] ?? 0) ?>").toFixed(2);

        // Page and Text Settings
        const textMargin = 20;
        const pageWidth = 190;

        // Add Page Border
        doc.setLineWidth(0.5);
        doc.rect(10, 10, 190, 277);

        // Title
        doc.setFontSize(18);
        doc.setFont("Helvetica", "bold");
        doc.text("TENANCY AGREEMENT", 105, 25, { align: "center" });

        // Introduction Section
        doc.setFontSize(12);
        doc.setFont("Helvetica", "normal");
        const introText = "This Tenancy Agreement is made and entered into on the date specified below:";
        doc.text(doc.splitTextToSize(introText, pageWidth - 2 * textMargin), textMargin, 40);
        doc.text(`Start Date: ${startDate}`, textMargin, 50);

        // Landlord and House Leader Section
        doc.setFont("Helvetica", "bold");
        doc.text("BETWEEN:", textMargin, 60);
        doc.setFont("Helvetica", "normal");
        const landlordText = `The Landlord: ${landlordName}, who agrees to lease the property to the House Leader and House Members specified below.`;
        doc.text(doc.splitTextToSize(landlordText, pageWidth - 2 * textMargin), textMargin + 10, 70);

        // House Members Section
        doc.setFont("Helvetica", "bold");
        doc.text("AND:", textMargin, 90);
        doc.setFont("Helvetica", "normal");
        const leaderText = `The House Leader: ${leaderName}, along with the following House Members:`;
        doc.text(doc.splitTextToSize(leaderText, pageWidth - 2 * textMargin), textMargin + 10, 100);
        doc.text(doc.splitTextToSize(membersList, pageWidth - 2 * textMargin), textMargin + 20, 110);

        // Agreement Details Section
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

    function validateAndDownload() {
        const agreementCheckbox = document.getElementById('agreementCheckbox');
        if (!agreementCheckbox.checked) {
            alert("You must agree to the terms before downloading.");
            return;
        }
        generateAgreementPDF();
    }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <main>
            <header>
                <h1>Tenancy Agreement</h1>
            </header>
            <div class="agreement-container">
                <section class="agreement-details-section">
                    <?php if ($agreement_details): ?>
                        <div class="agreement-details">
                            <h2 class="agreement-title">Agreement Details</h2>
                            <p class="agreement-detail"><strong>House Number:</strong> <?= htmlspecialchars($agreement_details['house_number']) ?></p>
                            <p class="agreement-detail"><strong>Landlord:</strong> <?= htmlspecialchars($agreement_details['landlord_name']) ?></p>
                            <p class="agreement-detail"><strong>House Leader:</strong> <?= htmlspecialchars($agreement_details['leader_name']) ?></p>
                            <p class="agreement-detail"><strong>House Members:</strong> <?= htmlspecialchars($agreement_details['members_list']) ?></p>
                            <p class="agreement-detail"><strong>Tenancy Period:</strong> <?= htmlspecialchars($agreement_details['tenancy_period']) ?></p>
                            <p class="agreement-detail"><strong>Start Date:</strong> <?= htmlspecialchars(date("d-m-Y", strtotime($agreement_details['start_date']))) ?></p>
                            <p class="agreement-detail"><strong>Monthly Rental:</strong> RM <?= htmlspecialchars(number_format($agreement_details['monthly_rent'], 2)) ?></p>
                            <p class="agreement-detail"><strong>Deposit:</strong> RM <?= htmlspecialchars(number_format($agreement_details['deposit'], 2)) ?></p>
                        </div>

                        <div class="agreement-consent">
                            <input type="checkbox" id="agreementCheckbox" class="checkbox" required>
                            <label for="agreementCheckbox" class="checkbox-label">
                                I agree this is a computer-generated agreement and does not require a physical signature.
                            </label>
                        </div>

                        <button type="button" class="button download-agreement-button" onclick="validateAndDownload()">Download Agreement</button>
                    <?php else: ?>
                        <p class="no-agreement-message">No agreement available for your house.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
