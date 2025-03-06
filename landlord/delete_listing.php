<?php
session_name("landlord_session");
session_start();
include '../db_connection.php';  // Include the database connection

// Ensure the landlord is logged in
if (!isset($_SESSION['landlord_id'])) {
    header("Location: landlord_login.php");
    exit();
}

$landlord_id = $_SESSION['landlord_id'];

// Check if the house_id is provided through GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['house_id'])) {
    $house_id = intval($_GET['house_id']);

    // Prepare the deletion query
    $delete_query = "DELETE FROM landlord_house WHERE house_id = ? AND landlord_id = ?";
    $stmt = $conn->prepare($delete_query);
    if (!$stmt) {
        // Redirect if there's an SQL error on statement preparation
        header("Location: landlord_dashboard.php?error=SQL+Error");
        exit();
    }

    // Bind parameters and execute the statement
    $stmt->bind_param("ii", $house_id, $landlord_id);
    $stmt->execute();

    // Check if the deletion was successful
    if ($stmt->affected_rows > 0) {
        header("Location: landlord_dashboard.php?success=House+deleted+successfully");
    } else {
        // Redirect if no rows were affected (house not found or not owned by the landlord)
        header("Location: landlord_dashboard.php?error=Failed+to+delete+house+or+house+not+found");
    }

    // Close the statement
    $stmt->close();
} else {
    // Redirect if the request is invalid (no house_id provided or wrong request method)
    header("Location: landlord_dashboard.php?error=Invalid+request");
}

// Close the database connection
$conn->close();
?>
