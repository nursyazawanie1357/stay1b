<?php
session_name("house_leader_session");
session_start();
include '../db_connection.php'; // Database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['expense_id'], $_FILES['receipt'])) {
    $expense_id = intval($_POST['expense_id']);

    // Validate uploaded file
    if ($_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../assets/receipts/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['receipt']['name']);
        $target_file = $target_dir . $file_name;

        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
            // Update receipt path in the database
            $receipt_path = "assets/receipts/" . $file_name;

            $stmt = $conn->prepare("UPDATE expense SET receipt_path = ? WHERE expense_id = ?");
            $stmt->bind_param("si", $receipt_path, $expense_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

header("Location: leader_expenses.php");
exit();
?>
