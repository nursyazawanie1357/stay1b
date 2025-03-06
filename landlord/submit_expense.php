<?php
// submit_expense.php
session_name("landlord_session");
session_start();
require '../db_connection.php';  // Ensure the path is correct
include 'landlord_sidebar.php';  // Includes the sidebar

if (!isset($_SESSION['landlord_id'])) {
    header("Location: ../landlord_login.php");
    exit();
}

$landlord_id = $_SESSION['landlord_id'];

// Check if the form data is posted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_expense'])) {
    // Extract and sanitize input
    $house_id = filter_input(INPUT_POST, 'house_id', FILTER_SANITIZE_NUMBER_INT);
    $expense_type = filter_input(INPUT_POST, 'expense_type', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'expense_amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);

    // Prepare SQL to insert the new expense
    $sql = "INSERT INTO expense (house_id, expense_type, expense_amount, due_date, expense_status) VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("isds", $house_id, $expense_type, $amount, $due_date);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Expense added successfully!";
        } else {
            echo "Error adding expense: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    $conn->close();
} else {
    // If the form is not posted
    echo "Invalid request.";
}
header('Location: landlord_expenses.php'); // Adjust the redirect as needed
exit();
?>
