<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

session_name("landlord_session");
session_start();
require "../db_connection.php"; // Ensure the path is correct
include "landlord_sidebar.php"; // Includes the sidebar

if (!isset($_SESSION["landlord_id"])) {
    header("Location: ../landlord_login.php");
    exit();
}

$landlord_id = $_SESSION["landlord_id"];

// Fetch current payment details for the landlord
$current_landlord_details = [];
$stmt = $conn->prepare("
    SELECT bank_name, account_number, account_holder_name, qr_code_path 
    FROM landlord_payment_details 
    WHERE landlord_id = ?
");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $current_landlord_details = $result->fetch_assoc();
}
$stmt->close();

// Fetch landlord's houses for the dropdown
$houses = [];
$query_houses =
    "SELECT house_id, house_number FROM landlord_house WHERE landlord_id = ?";
$stmt = $conn->prepare($query_houses);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $houses[] = $row;
}
$stmt->close();

// Handle form submission to update payment details
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_landlord_payment_details"])
) {
    $bank_name = $_POST["bank_name"] ?? "";
    $account_number = $_POST["account_number"] ?? "";
    $account_holder = $_POST["account_holder"] ?? "";
    $qr_code_path = $current_landlord_details["qr_code_path"] ?? "";

    // Handle QR code upload
    if (
        isset($_FILES["qr_code"]) &&
        $_FILES["qr_code"]["error"] === UPLOAD_ERR_OK
    ) {
        $target_dir = $_SERVER["DOCUMENT_ROOT"] . "/Stay1B/assets/QRcodes/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . "_" . basename($_FILES["qr_code"]["name"]);
        $target_file = $target_dir . $file_name;

        $check = getimagesize($_FILES["qr_code"]["tmp_name"]);
        if (
            $check !== false &&
            move_uploaded_file($_FILES["qr_code"]["tmp_name"], $target_file)
        ) {
            $qr_code_path = "Stay1B/assets/QRcodes/" . $file_name;
        }
    }

    // Insert or update landlord payment details
    $stmt = $conn->prepare("
        INSERT INTO landlord_payment_details (landlord_id, bank_name, account_number, account_holder_name, qr_code_path) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            bank_name = VALUES(bank_name), 
            account_number = VALUES(account_number), 
            account_holder_name = VALUES(account_holder_name), 
            qr_code_path = VALUES(qr_code_path)
    ");
    $stmt->bind_param(
        "issss",
        $landlord_id,
        $bank_name,
        $account_number,
        $account_holder,
        $qr_code_path,
    );
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER["PHP_SELF"]);
    exit();
}

// Variables for form editing
$edit_mode = false;
$edit_data = [];

// Handle form submission to add or update an expense
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_expense"])) {
    $house_id = $_POST["house_id"];
    $expense_type = $_POST["expense_type"];
    $amount = $_POST["expense_amount"];
    $due_date = $_POST["due_date"];


    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["expense_amount"])) {
        $amount = $_POST["expense_amount"];
        
        // Validate and sanitize the input
        if (!filter_var($amount, FILTER_VALIDATE_FLOAT)) {
            die("Invalid amount entered. Please enter a valid decimal number.");
        }
    
        // Convert the amount to a consistent format (if needed)
        $amount = number_format((float)$amount, 2, '.', '');
    }
    

    if (isset($_POST["expense_id"]) && !empty($_POST["expense_id"])) {
        $expense_id = $_POST["expense_id"];
        $query_update_expense =
            "UPDATE expense SET house_id = ?, expense_type = ?, expense_amount = ?, due_date = ? WHERE expense_id = ?";
        $stmt = $conn->prepare($query_update_expense);
        $stmt->bind_param(
            "isdsi",
            $house_id,
            $expense_type,
            $amount,
            $due_date,
            $expense_id,
        );
        $stmt->execute();
        $stmt->close();
    } else {
        $query_add_expense =
            "INSERT INTO expense (house_id, expense_type, expense_amount, due_date, expense_status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($query_add_expense);
        $stmt->bind_param("isds", $house_id, $expense_type, $amount, $due_date);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER["PHP_SELF"]);
    exit();
}

// Handle actions for expense status update, edit, and delete
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (isset($_GET["action"]) && isset($_GET["expense_id"])) {
        $expense_id = intval($_GET["expense_id"]);
        $action = $_GET["action"];

        // Handle status updates
        if (in_array($action, ["mark_successful", "mark_failed"])) {
            $new_status = "";
            switch ($action) {
                case "mark_successful":
                    $new_status = "successful";
                    break;
                case "mark_failed":
                    $new_status = "failed";
                    break;
            }

            if (!empty($new_status)) {
                $stmt = $conn->prepare(
                    "UPDATE expense SET expense_status = ? WHERE expense_id = ?"
                );
                $stmt->bind_param("si", $new_status, $expense_id);
                $stmt->execute();
                $stmt->close();
            }

            header("Location: " . $_SERVER["PHP_SELF"]);
            exit();
        }
        // Handle delete expense
        if ($action === "delete") {
            $stmt = $conn->prepare("DELETE FROM expense WHERE expense_id = ?");
            $stmt->bind_param("i", $expense_id);
            $stmt->execute();
            $stmt->close();

            header("Location: " . $_SERVER["PHP_SELF"]);
            exit();
        }

        // Handle edit expense
        if ($action === "edit") {
            $stmt = $conn->prepare(
                "SELECT house_id, expense_type, expense_amount, due_date FROM expense WHERE expense_id = ?",
            );
            $stmt->bind_param("i", $expense_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $edit_data = $result->fetch_assoc();
                $edit_mode = true;
                $edit_data["expense_id"] = $expense_id; // Add expense_id to edit_data
            }
            $stmt->close();
        }
    }
}

$receipt_path = ""; // Initialize to prevent undefined variable error
$file_name = ""; // Initialize to prevent undefined variable error

if (
    isset($_FILES["receipt"]) &&
    $_FILES["receipt"]["error"] === UPLOAD_ERR_OK
) {
    $target_dir = "../assets/receipts/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = time() . "_" . basename($_FILES["receipt"]["name"]);
    $target_file = $target_dir . $file_name;

    // Move the uploaded file to the target directory
    if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_file)) {
        $receipt_path = "/assets/receipts/" . $file_name;
    }
}

// Update the database
if (!empty($receipt_path)) {
    $stmt = $conn->prepare(
        "UPDATE expense SET receipt_path = ? WHERE expense_id = ?",
    );
    $stmt->bind_param("si", $receipt_path, $expense_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch expenses for display
$expenses = [];
$query_expenses = "
    SELECT e.expense_id, e.expense_type, e.expense_amount, e.due_date, e.expense_status, e.receipt_path, lh.house_number
    FROM expense e
    JOIN landlord_house lh ON e.house_id = lh.house_id
    WHERE lh.landlord_id = ?
    ORDER BY e.expense_id DESC"; // Order by newest expenses first
$stmt = $conn->prepare($query_expenses);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$stmt->bind_result(
    $expense_id,
    $expense_type,
    $expense_amount,
    $due_date,
    $expense_status,
    $receipt_path,
    $house_number
);

while ($stmt->fetch()) {
    $expenses[] = [
        "id" => $expense_id,
        "type" => $expense_type,
        "amount" => $expense_amount,
        "due_date" => $due_date,
        "status" => $expense_status,
        "receipt" => $receipt_path,
        "house_number" => $house_number,
    ];
}

$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payment Details and Expenses - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
 
    <script>
        // Automatically show the add-expense-form when in edit mode
        document.addEventListener("DOMContentLoaded", function() {
            const isEditMode = < ? = json_encode($edit_mode); ? > ;
            if (isEditMode) {
                document.getElementById("add-expense-form").classList.remove("hidden");
            }
        });
    </script>
    
    <script>
        function toggleSection(id) {
            const sections = document.querySelectorAll('section, form');
            sections.forEach(section => section.classList.add('hidden'));
            const target = document.getElementById(id);
            if (target) target.classList.remove('hidden');
        }
    </script>
</head>

<body>
    <div class="dashboard-container">
        <main>
            <header>
                <h1>Payment Details and Expenses</h1>
            </header>
            <!-- Toggle Buttons -->
            <div class="toggle-buttons-container">
                <button class="toggle-button show-payment-details-button" onclick="toggleSection('paymentDetails')">Payment Details</button>
                <button class="toggle-button show-manage-expenses-button" onclick="toggleSection('houseExpensesSection')">Manage Expenses</button>
            </div>

    <section id="paymentDetails" class="toggle-section hidden">
    <h2>Landlord Payment Details</h2>
    <div class="payment-details-content">
        <p><strong>Bank Name:</strong> <?= htmlspecialchars(
            $current_landlord_details["bank_name"] ?? "Not set",
        ) ?></p>
        <p><strong>Account Number:</strong> <?= htmlspecialchars(
            $current_landlord_details["account_number"] ?? "Not set",
        ) ?></p>
        <p><strong>Account Holder Name:</strong> <?= htmlspecialchars(
            $current_landlord_details["account_holder_name"] ?? "Not set",
        ) ?></p>
        <p><strong>QR Code:</strong></p>
        <?php if (
            !empty($current_landlord_details["qr_code_path"]) &&
            file_exists(
                $_SERVER["DOCUMENT_ROOT"] .
                    "/" .
                    $current_landlord_details["qr_code_path"],
            )
        ): ?>
            <img src="/<?= htmlspecialchars(
                $current_landlord_details["qr_code_path"],
            ) ?>" alt="QR Code" class="qr-code-img">
        <?php else: ?>
            <p>No QR Code available.</p>
        <?php endif; ?>
        <button class="edit-button" onclick="toggleSection('editLandlordForm')">Edit Details</button>
    </div>
</section>

<!-- Edit Payment Details Form -->
<form id="editLandlordForm" class="hidden" method="post" enctype="multipart/form-data">
    <label for="bank_name">Bank Name:</label>
    <input type="text" id="bank_name" name="bank_name" value="<?= htmlspecialchars(
        $current_landlord_details["bank_name"] ?? "",
    ) ?>" required>
    <label for="account_number">Account Number:</label>
    <input type="text" id="account_number" name="account_number" value="<?= htmlspecialchars(
        $current_landlord_details["account_number"] ?? "",
    ) ?>" required>
    <label for="account_holder">Account Holder Name:</label>
    <input type="text" id="account_holder" name="account_holder" value="<?= htmlspecialchars(
        $current_landlord_details["account_holder_name"] ?? "",
    ) ?>" required>
    <label for="qr_code">Upload QR Code:</label>
    <input type="file" id="qr_code" name="qr_code" accept="image/*">
    <input type="submit" name="update_landlord_payment_details" value="Update Payment Details">
</form>

<!-- Expenses Table Section -->
<section id="houseExpensesSection" class="toggle-section">
    <h2>Manage House Expenses</h2>
    <table>
        <thead>
            <tr>
                <th>House Number</th>
                <th>Expense Type</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Receipt</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
    <?php if (!empty($expenses)): ?>
        <?php 
        $current_house = null; 
        $row_count = 0; 
        $totals = []; // To track totals for each house_number
        ?>
        <?php foreach ($expenses as $key => $expense): ?>
            <?php 
            // Calculate totals for each house
            if (!isset($totals[$expense["house_number"]])) {
                $totals[$expense["house_number"]] = 0;
            }
            $totals[$expense["house_number"]] += $expense["amount"]; 
            ?>
            <tr>
                <?php if ($expense["house_number"] !== $current_house): ?>
                    <?php 
                    // Count rows for this house
                    $row_count = count(array_filter($expenses, function ($e) use ($expense) {
                        return $e["house_number"] === $expense["house_number"];
                    }));
                    $current_house = $expense["house_number"]; 
                    ?>
                    <td rowspan="<?= $row_count + 1 // Add 1 for the total row ?>">
                        <?= htmlspecialchars($expense["house_number"]) ?>
                    </td>
                <?php endif; ?>
                <td><?= htmlspecialchars($expense["type"]) ?></td>
                <td>RM <?= htmlspecialchars(number_format($expense["amount"], 2)) ?></td>
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
    <?= htmlspecialchars($expense["status"]) ?><br>
    <?php if ($expense["status"] === "pending"): ?>
        <a href="?action=mark_successful&expense_id=<?= $expense["id"] ?>" class="status-link mark-success">Success</a>
        <a href="?action=mark_failed&expense_id=<?= $expense["id"] ?>" class="status-link mark-failed">Failed</a>
    <?php endif; ?>
</td>

                <td>
                    <?php if (!empty($expense["receipt"])): ?>
                        <?php 
                        $receipt_url = "/Stay1B/" . ltrim($expense["receipt"], "/");
                        $receipt_file = $_SERVER["DOCUMENT_ROOT"] . "/Stay1B/" . ltrim($expense["receipt"], "/");
                        ?>
                        <?php if (file_exists($receipt_file)): ?>
                            <a href="<?= htmlspecialchars($receipt_url) ?>" target="_blank">View Receipt</a>
                        <?php else: ?>
                            Receipt file not found.
                        <?php endif; ?>
                    <?php else: ?>
                        No Receipt
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?action=edit&expense_id=<?= $expense["id"] ?>" class="action-link edit-link">Edit</a>
                    <a href="?action=delete&expense_id=<?= $expense["id"] ?>" class="action-link delete-link" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php 
            // Add a total row after the last expense of the current house
            $next_expense = $expenses[$key + 1] ?? null;
            if (!$next_expense || $next_expense["house_number"] !== $expense["house_number"]): 
            ?>
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">Total Amount:</td>
                    <td colspan="2" style="font-weight: bold;">RM <?= htmlspecialchars(number_format($totals[$expense["house_number"]], 2)) ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" style="text-align: center;">No expenses recorded yet.</td>
        </tr>
    <?php endif; ?>
</tbody>

    
    </table>

    <button class="edit-button" onclick="toggleSection('add-expense-form')">Add Expense</button>
    
</section>
        
<!-- Form for Adding/Editing Expense -->
<form id="add-expense-form" action="" method="POST" class="<?= $edit_mode ? "" : "hidden" ?>">
    <input type="hidden" name="submit_expense" value="1">
    <?php if ($edit_mode): ?>
        <input type="hidden" name="expense_id" value="<?= htmlspecialchars($edit_data["expense_id"]) ?>">
    <?php endif; ?>
    <label for="house-id">Select House:</label>
    <select id="house-id" name="house_id" required>
        <option value="" disabled <?= !$edit_mode ? "selected" : "" ?>>Please select a house</option>
        <?php foreach ($houses as $house): ?>
            <option value="<?= htmlspecialchars($house["house_id"]) ?>" <?= $edit_mode && $edit_data["house_id"] == $house["house_id"] ? "selected" : "" ?>>
                <?= htmlspecialchars($house["house_number"]) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label for="expense-type">Expense Type:</label>
    <input type="text" id="expense-type" name="expense_type" value="<?= $edit_mode ? htmlspecialchars($edit_data["expense_type"]) : "" ?>" placeholder="Please enter expense type" required>
    <label for="amount">Amount (RM):</label>
    <input type="number" id="amount" name="expense_amount" 
       step="0.01" min="0" 
       value="<?= $edit_mode ? htmlspecialchars($edit_data["expense_amount"]) : "" ?>" 
       placeholder="Please enter amount" required>
    <label for="due-date">Due Date:</label>
    <input type="date" id="due-date" name="due_date" value="<?= $edit_mode ? htmlspecialchars($edit_data["due_date"]) : "" ?>" required>
    <button type="submit"><?= $edit_mode ? "Update Expense" : "Add Expense" ?></button>
</form>


        </main>
    </div>
</body>

</html>