<?php
session_name("house_member_session");
session_start();
include '../db_connection.php'; // Database connection file
include 'member_sidebar.php'; // Sidebar for members

// Check if the member is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: member_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id'];

// Calculate number of people in the house group
$query_group_size = "
    SELECT COUNT(*) as group_size 
    FROM house_group 
    WHERE booked_house_id = (
        SELECT booked_house_id 
        FROM house_group 
        WHERE housemate_id = ?
    )";
$stmt_group_size = $conn->prepare($query_group_size);
$stmt_group_size->bind_param("i", $housemate_id);
$stmt_group_size->execute();
$stmt_group_size->bind_result($group_size);
$stmt_group_size->fetch();
$stmt_group_size->close();

// Handle receipt upload
if (isset($_POST['upload_receipt']) && isset($_FILES['receipt'])) {
    $expense_id = $_POST['expense_id'];
    $receiptFile = $_FILES['receipt'];

    // Check if file was uploaded without errors
    if ($receiptFile['error'] == 0) {
        $fileName = $receiptFile['name'];
        $fileTmpPath = $receiptFile['tmp_name'];
        $fileType = $receiptFile['type'];
        $fileSize = $receiptFile['size'];
        
        // Define allowed file types and size limit
        $allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5 MB

        // Check file type and size
        if (in_array($fileType, $allowedFileTypes) && $fileSize <= $maxFileSize) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '\Stay1B\assets\member_receipts\\';
            $uploadPath = $uploadDir . basename($fileName);

            // Ensure upload directory exists or attempt to create it
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Move the file to the desired directory
            if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                // Fetch the expense amount to calculate the divided amount
                $query_expense_amount = "SELECT expense_amount FROM expense WHERE expense_id = ?";
                $stmt_expense_amount = $conn->prepare($query_expense_amount);
                $stmt_expense_amount->bind_param("i", $expense_id);
                $stmt_expense_amount->execute();
                $stmt_expense_amount->bind_result($expense_amount);
                $stmt_expense_amount->fetch();
                $stmt_expense_amount->close();

                // Calculate the divided amount
                $divided_amount = $expense_amount / $group_size;

                // Upsert the receipt path and divided amount
                $query = "INSERT INTO member_expenses (expense_id, housemate_id, receipt_path, divided_amount) VALUES (?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE receipt_path = VALUES(receipt_path), divided_amount = VALUES(divided_amount)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iisd", $expense_id, $housemate_id, $uploadPath, $divided_amount);
                $stmt->execute();

                $stmt->close();
                header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page
                exit;
            } else {
                echo "<p>Error moving the file.</p>";
            }
        } else {
            echo "<p>Invalid file type or size. Please upload a JPEG, PNG, or GIF file not exceeding 5 MB.</p>";
        }
    } else {
        echo "<p>Error uploading file. Error code: " . $receiptFile['error'] . "</p>";
    }
}

// Fetch all expenses for the house group, calculate the member's share, ordered by date
$query_expenses = "
    SELECT 
        e.expense_id, 
        e.expense_type, 
        e.expense_amount, 
        e.due_date, 
        me.receipt_path, 
        me.member_payment_status 
    FROM expense e
    LEFT JOIN member_expenses me ON e.expense_id = me.expense_id AND me.housemate_id = ?
    JOIN house_booking hb ON e.house_id = hb.house_id
    WHERE hb.booked_house_id = (
        SELECT booked_house_id 
        FROM house_group 
        WHERE housemate_id = ?
    )
    ORDER BY e.due_date DESC";
$stmt_expenses = $conn->prepare($query_expenses);
$stmt_expenses->bind_param("ii", $housemate_id, $housemate_id);
$stmt_expenses->execute();
$resultExpenses = $stmt_expenses->get_result();

$total_amount = 0;
$total_divided = 0;
while ($expense = $resultExpenses->fetch_assoc()) {
    $total_amount += $expense['expense_amount'];
    $total_divided += ($expense['expense_amount'] / $group_size);
}

$resultExpenses->data_seek(0); // Reset the pointer to the beginning for display


// Fetch leader's payment details
$query_leader_payment = "
    SELECT bank_name, account_number, account_holder_name, qr_code_path 
    FROM leader_payment_details 
    WHERE housemate_id = (
        SELECT housemate_id 
        FROM housemate_role 
        WHERE house_role = 'leader' AND housemate_id IN (
            SELECT housemate_id 
            FROM house_group 
            WHERE booked_house_id = (
                SELECT booked_house_id 
                FROM house_group 
                WHERE housemate_id = ?
            )
        )
    )";
$stmt = $conn->prepare($query_leader_payment);
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$result = $stmt->get_result();
$leader_payment_details = $result->fetch_assoc();
$stmt->close();

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
        function toggleSection(sectionId) {
            const sections = document.querySelectorAll('.toggle-section');
            sections.forEach(section => section.classList.add('hidden'));
            document.getElementById(sectionId).classList.remove('hidden');
        }
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
                <button onclick="toggleSection('paymentDetailsSection')">Leader Payment Details</button>
                <button onclick="toggleSection('manageExpensesSection')">Manage My Expenses</button>
            </div>

            <!-- Leader Payment Details Section -->
            <section id="paymentDetailsSection" class="toggle-section hidden">
                <h2>Leader Payment Details</h2>
                <?php if (!empty($leader_payment_details)): ?>
                    <div class="payment-details-content">
                        <p><strong>Bank Name:</strong> <?= htmlspecialchars($leader_payment_details['bank_name']); ?></p>
                        <p><strong>Account Number:</strong> <?= htmlspecialchars($leader_payment_details['account_number']); ?></p>
                        <p><strong>Account Holder Name:</strong> <?= htmlspecialchars($leader_payment_details['account_holder_name']); ?></p>
                        <p><strong>QR Code:</strong>
                            <?php if (!empty($leader_payment_details['qr_code_path'])): ?>
                                <img src="/<?= htmlspecialchars($leader_payment_details['qr_code_path']); ?>" alt="QR Code" class="qr-code-img">
                            <?php else: ?>
                                <p>No QR Code available.</p>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <p>No leader payment details found.</p>
                <?php endif; ?>
            </section>

   <!-- Manage Expenses Section -->
   <section id="manageExpensesSection" class="toggle-section">
    <h2>Manage My Expenses</h2>
    <?php if ($resultExpenses->num_rows > 0): ?>
        <table class="house-leader-expenses-table">
            <thead>
                <tr>
                    <th>Expense Type</th>
                    <th>Amount</th>
                    <th>Individual Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Receipt</th>
                    <th>Upload Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($expense = $resultExpenses->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($expense['expense_type']); ?></td>
                        <td>RM<?= number_format($expense['expense_amount'], 2); ?></td>
                        <td>RM<?= number_format($expense['expense_amount'] / $group_size, 2); ?></td>
                        <td>
                            <?= $expense['due_date'] !== '0000-00-00' ? htmlspecialchars(date("d/m/Y", strtotime($expense['due_date']))) : "Not Set"; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars(ucfirst($expense['member_payment_status'] ?? 'pending')); ?>
                        </td>
                        <td>
                            <?php
                            $receiptPath = '/Stay1B/assets/member_receipts/' . basename($expense['receipt_path']);
                            if (!empty($expense['receipt_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $receiptPath)): ?>
                                <a href="<?= htmlspecialchars($receiptPath); ?>" target="_blank">View Receipt</a>
                            <?php else: ?>
                                <span>No Receipt</span>
                            <?php endif; ?>
                        </td>
                        <td>
    <?php if ($expense['member_payment_status'] === 'successful'): ?>
        <span>Uploaded</span>
    <?php else: ?>
        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="expense_id" value="<?= $expense['expense_id']; ?>">
            <input type="file" name="receipt" required>
            <button type="submit" name="upload_receipt">Upload</button>
        </form>
    <?php endif; ?>
</td>

                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" style="text-align: right;">
                        <strong>Total Amount: RM<?= number_format($total_amount, 2); ?></strong><br>
                        <strong>Total Individual Amount: RM<?= number_format($total_divided, 2); ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    <?php else: ?>
        <p>No expenses recorded yet.</p>
    <?php endif; ?>
</section>


        </main>
    </div>
</body>
</html>
