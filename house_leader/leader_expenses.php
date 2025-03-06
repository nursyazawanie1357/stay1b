<?php
session_name("house_leader_session");
session_start();
include '../db_connection.php'; // Database connection file
include 'leader_sidebar.php'; // Sidebar for house leaders

// Check if the house leader is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: leader_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id']; // Leader's housemate ID


// Fetch the booked house ID associated with the leader
$sqlBookedHouse = "
    SELECT booked_house_id 
    FROM house_group 
    WHERE housemate_id = ?
";
$stmt = $conn->prepare($sqlBookedHouse);
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$resultBookedHouse = $stmt->get_result();
$booked_house_id = null;

if ($resultBookedHouse->num_rows > 0) {
    $bookedHouse = $resultBookedHouse->fetch_assoc();
    $booked_house_id = $bookedHouse['booked_house_id'];
} else {
    // Remove or comment this debug line
    // echo "<p>Debug: No booked house ID found for housemate ID: $housemate_id</p>";
}


$stmt->close();

// Fetch housemate IDs for the house group associated with the booked house
$housemate_ids = [];
if (!empty($booked_house_id)) {
    $stmtHousemates = $conn->prepare("
        SELECT housemate_id
        FROM house_group
        WHERE booked_house_id = ?
    ");
    $stmtHousemates->bind_param("i", $booked_house_id);
    $stmtHousemates->execute();
    $resultHousemates = $stmtHousemates->get_result();

    while ($housemate = $resultHousemates->fetch_assoc()) {
        $housemate_ids[] = $housemate['housemate_id'];
    }
    $stmtHousemates->close();
}

// Debug housemate IDs
if (empty($housemate_ids)) {
    // Remove or comment this debug line
    // echo "<p>Debug: No housemates found for booked house ID: $booked_house_id</p>";
} else {
    // Remove or comment this debug line
    // echo "<p>Debug: Housemate IDs found: " . implode(", ", $housemate_ids) . "</p>";
}

// Fetch expenses for these housemates along with their names
$memberExpenses = [];
if (!empty($housemate_ids)) {
    $in = str_repeat('?,', count($housemate_ids) - 1) . '?';
    $stmtExpenses = $conn->prepare("
SELECT 
    me.housemate_id, 
    t.tenant_full_name AS housemate_name, 
    me.expense_id, 
    me.divided_amount, 
    e.expense_type, 
    e.due_date, 
    me.receipt_path,
    me.member_payment_status AS status
FROM member_expenses me
JOIN expense e ON me.expense_id = e.expense_id
JOIN housemate_role hr ON me.housemate_id = hr.housemate_id
JOIN tenant t ON hr.tenant_id = t.tenant_id
WHERE me.housemate_id IN ($in)
ORDER BY e.due_date DESC;
    ");
    $stmtExpenses->bind_param(str_repeat("i", count($housemate_ids)), ...$housemate_ids);
    $stmtExpenses->execute();
    $resultExpenses = $stmtExpenses->get_result();

    while ($expense = $resultExpenses->fetch_assoc()) {
        $memberExpenses[] = $expense;
    }
    $stmtExpenses->close();
}

// Action handling section
if (isset($_GET['action']) && isset($_GET['expense_id'])) {
    $action = $_GET['action'];
    $expense_id = intval($_GET['expense_id']);
    $current_section = isset($_GET['section']) ? $_GET['section'] : '';

    $new_status = null;
    if ($action === 'mark_successful') {
        $new_status = 'successful';
    } elseif ($action === 'mark_failed') {
        $new_status = 'failed';
    }

    if ($new_status !== null) {
        $stmtUpdateStatus = $conn->prepare("
            UPDATE member_expenses 
            SET member_payment_status = ? 
            WHERE expense_id = ?
        ");
        $stmtUpdateStatus->bind_param("si", $new_status, $expense_id);
        $stmtUpdateStatus->execute();
        $stmtUpdateStatus->close();

        // Redirect to the same page, preserving the section
        header("Location: " . $_SERVER['PHP_SELF'] . "?section=" . urlencode($current_section));
        exit();
    }
}

// Fetch landlord payment details for the booked house
$landlord_details = [];
if (!empty($booked_house_id)) {
    $stmt = $conn->prepare("
        SELECT 
            lpd.bank_name, 
            lpd.account_number, 
            lpd.account_holder_name, 
            lpd.qr_code_path 
        FROM landlord_payment_details lpd
        JOIN landlord_house lh ON lpd.landlord_id = lh.landlord_id
        JOIN house_booking hb ON lh.house_id = hb.house_id
        WHERE hb.booked_house_id = ? AND hb.booking_status = 'approved'
    ");
    $stmt->bind_param("i", $booked_house_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $landlord_details = $result->fetch_assoc();
    }

    $stmt->close();
}

// Fetch current payment details for the leader
$current_leader_details = [];
$stmt = $conn->prepare("
    SELECT bank_name, account_number, account_holder_name, qr_code_path 
    FROM leader_payment_details 
    WHERE housemate_id = ?
");
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $current_leader_details = $result->fetch_assoc();
}
$stmt->close();

// Handle form submission for updating leader payment details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_leader_payment_details'])) {
    $bank_name = $_POST['bank_name'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $account_holder = $_POST['account_holder'] ?? '';
    $qr_code_path = $current_leader_details['qr_code_path'] ?? '';

    // Handle QR code upload
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/Stay1B/assets/QRcodes/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['qr_code']['name']);
        $target_file = $target_dir . $file_name;

        $check = getimagesize($_FILES['qr_code']['tmp_name']);
        if ($check !== false && move_uploaded_file($_FILES['qr_code']['tmp_name'], $target_file)) {
            $qr_code_path = "Stay1B/assets/QRcodes/" . $file_name;
        }
    }

    // Insert or update leader payment details
    $stmt = $conn->prepare("
        INSERT INTO leader_payment_details (housemate_id, bank_name, account_number, account_holder_name, qr_code_path) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            bank_name = VALUES(bank_name), 
            account_number = VALUES(account_number), 
            account_holder_name = VALUES(account_holder_name), 
            qr_code_path = VALUES(qr_code_path)
    ");
    $stmt->bind_param("issss", $housemate_id, $bank_name, $account_number, $account_holder, $qr_code_path);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all expenses for the booked house, ordered by due date descending or by the expense_id if the dates are the same
$sqlExpenses = "SELECT expense_id, expense_type, expense_amount, due_date, expense_status, receipt_path 
                FROM expense 
                WHERE house_id = (
                    SELECT house_id FROM house_booking WHERE booked_house_id = ?
                )
                ORDER BY due_date DESC, expense_id DESC";
$stmtExpenses = $conn->prepare($sqlExpenses);
$stmtExpenses->bind_param("i", $booked_house_id);
$stmtExpenses->execute();
$resultExpenses = $stmtExpenses->get_result();



$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Expenses - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <script>
        // Show one section and hide the others
        function toggleSection(sectionId) {
            const sections = document.querySelectorAll('.toggle-section');
            sections.forEach(section => section.classList.add('hidden'));
            document.getElementById(sectionId).classList.remove('hidden');
        }

// Show the correct section based on the "section" parameter in the URL
document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get("section");
    if (section) {
        toggleSection(section);
    }
});


    </script>
</head>
<body>
    <div class="dashboard-container">
        <main>
            <header class="house-leader-header">
                <h1>House Expenses</h1>
            </header>

            <!-- Toggle Buttons -->
            <div class="house-leader-toggle-buttons">
                <button onclick="toggleSection('paymentDetailsSection')">Payment Details</button>
                <button onclick="toggleSection('houseExpensesSection')">Manage House Expenses</button>
                <button onclick="toggleSection('memberExpensesSection')">Manage Member Expenses</button>
            </div>

            <!-- Payment Details Section -->
            <section id="paymentDetailsSection" class="toggle-section hidden">
                <h2>Landlord Payment Details</h2>
                <?php if (!empty($landlord_details)): ?>
                    <div class="payment-details-content">
                        <p><strong>Bank Name:</strong> <?= htmlspecialchars($landlord_details['bank_name']); ?></p>
                        <p><strong>Account Number:</strong> <?= htmlspecialchars($landlord_details['account_number']); ?></p>
                        <p><strong>Account Holder Name:</strong> <?= htmlspecialchars($landlord_details['account_holder_name']); ?></p>
                        <p><strong>QR Code:</strong></p>
                        <?php if (!empty($landlord_details['qr_code_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $landlord_details['qr_code_path'])): ?>
                            <img src="/<?= htmlspecialchars($landlord_details['qr_code_path']); ?>" alt="QR Code" class="qr-code-img">
                        <?php else: ?>
                            <p>No QR Code available.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>No landlord payment details found for this house.</p>
                <?php endif; ?>

                <!-- Leader Payment Details -->
                <h2>My Payment Details</h2>
                <div class="payment-details-content">
                    <p><strong>Bank Name:</strong> <?= htmlspecialchars($current_leader_details['bank_name'] ?? 'Not set'); ?></p>
                    <p><strong>Account Number:</strong> <?= htmlspecialchars($current_leader_details['account_number'] ?? 'Not set'); ?></p>
                    <p><strong>Account Holder Name:</strong> <?= htmlspecialchars($current_leader_details['account_holder_name'] ?? 'Not set'); ?></p>
                    <p><strong>QR Code:</strong></p>
                    <?php if (!empty($current_leader_details['qr_code_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $current_leader_details['qr_code_path'])): ?>
                        <img src="/<?= htmlspecialchars($current_leader_details['qr_code_path']); ?>" alt="QR Code" class="qr-code-img">
                    <?php else: ?>
                        <p></p>
                    <?php endif; ?>
                    <button class="edit-button" onclick="toggleSection('editLeaderForm')">Edit Details</button>
                </div>

                </section>

                <!-- Edit Leader Payment Details Form -->
                <form id="editLeaderForm" class="hidden" method="post" enctype="multipart/form-data">
                    <label for="bank_name">Bank Name:</label>
                    <input type="text" id="bank_name" name="bank_name" value="<?= htmlspecialchars($current_leader_details['bank_name'] ?? '') ?>" required>
                    <label for="account_number">Account Number:</label>
                    <input type="text" id="account_number" name="account_number" value="<?= htmlspecialchars($current_leader_details['account_number'] ?? '') ?>" required>
                    <label for="account_holder">Account Holder Name:</label>
                    <input type="text" id="account_holder" name="account_holder" value="<?= htmlspecialchars($current_leader_details['account_holder_name'] ?? '') ?>" required>
                    <label for="qr_code">Upload QR Code:</label>
                    <input type="file" id="qr_code" name="qr_code" accept="image/*">
                    <input type="submit" name="update_leader_payment_details" value="Update Payment Details">
                </form><!-- House Expenses Section -->

<section id="houseExpensesSection" class="toggle-section">
    <h2>Manage House Expenses</h2>
    <?php if ($resultExpenses && $resultExpenses->num_rows > 0): ?>
        <table class="house-leader-expenses-table">
            <thead>
                <tr>
                    <th>Expense Type</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Receipt</th>
                    <th>Upload Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php $total_expenses = 0; ?>
                <?php while ($expense = $resultExpenses->fetch_assoc()): ?>
                    <?php $total_expenses += $expense['expense_amount']; ?>
                    <tr>
                        <td><?= htmlspecialchars($expense['expense_type']); ?></td>
                        <td>RM<?= number_format($expense['expense_amount'], 2); ?></td>
                        <td>
                            <?php 
                            if ($expense['due_date'] !== '0000-00-00') {
                                $formattedDate = date("d/m/Y", strtotime($expense['due_date']));
                                echo htmlspecialchars($formattedDate);
                            } else {
                                echo "Not Set";
                            }
                            ?>
                        </td>
                        <td><?= ucfirst(htmlspecialchars($expense['expense_status'])); ?></td>
                        <td>
                            <?php if (!empty($expense['receipt_path'])): ?>
                                <?php
                                // Correctly format the receipt path
                                $receipt_url = '/Stay1B/' . ltrim($expense['receipt_path'], '/');
                                $receipt_file = $_SERVER['DOCUMENT_ROOT'] . '/Stay1B/' . ltrim($expense['receipt_path'], '/');
                                ?>
                                <?php if (file_exists($receipt_file)): ?>
                                    <a href="<?= htmlspecialchars($receipt_url); ?>" target="_blank">View Receipt</a>
                                <?php else: ?>
                                    <span>Receipt file not found.</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span>No Receipt</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($expense['expense_status'] === 'pending'): ?>
                                <form action="leader_receipt.php" method="POST" enctype="multipart/form-data" class="house-leader-upload-form">
                                    <input type="hidden" name="expense_id" value="<?= $expense['expense_id']; ?>">
                                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <button type="submit">Upload</button>
                                </form>
                            <?php else: ?>
                                <span>Uploaded</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align: right;">
                        <strong>Total Amount: RM<?= number_format($total_expenses, 2); ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    <?php else: ?>
        <p>No house expenses yet.</p>
    <?php endif; ?>
</section>

<!-- Member Expenses Section -->
<section id="memberExpensesSection" class="toggle-section hidden">
    <h2>Manage Member Expenses</h2>
    <?php if (!empty($memberExpenses)): ?>
        <table class="house-leader-expenses-table">
        <thead>
            <tr>
                <th>House Member</th>
                <th>Expense Type</th>
                <th>Individual Amount</th>
                <th>Due Date</th>
                <th>Receipt</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($memberExpenses as $expense): ?>
                <tr>
                    <td><?= htmlspecialchars($expense['housemate_name']); ?></td>
                    <td><?= htmlspecialchars($expense['expense_type']); ?></td>
                    <td>RM<?= number_format($expense['divided_amount'], 2); ?></td>
                    <td>
                        <?php 
                        if ($expense['due_date'] !== '0000-00-00') {
                            $formattedDate = date("d/m/Y", strtotime($expense['due_date']));
                            echo htmlspecialchars($formattedDate);
                        } else {
                            echo "Not Set";
                        }
                        ?>
                    </td>
                    <td>
                        <?php if (!empty($expense['receipt_path'])): ?>
                            <a href="/Stay1B/assets/member_receipts/<?= basename($expense['receipt_path']); ?>" target="_blank">View Receipt</a>
                        <?php else: ?>
                            <span>No Receipt</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($expense["status"]); ?></td>
                    <td>
                        <?php if ($expense["status"] === "pending"): ?>
                            <a href="?action=mark_successful&expense_id=<?= $expense["expense_id"] ?>&section=memberExpensesSection" class="status-link mark-success">Success</a>
                            <a href="?action=mark_failed&expense_id=<?= $expense["expense_id"] ?>&section=memberExpensesSection" class="status-link mark-failed">Failed</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    <?php else: ?>
        <p>No member expenses yet.</p>
    <?php endif; ?>
</section>



        </main>
    </div>
</body>

</html>