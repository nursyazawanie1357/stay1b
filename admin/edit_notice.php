<?php
session_name("admin_session");
session_start();
include '../db_connection.php';  // Open database connection

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && isset($_GET['notice_id'])) {
    $action = $_GET['action'];
    $notice_id = $_GET['notice_id'];

    switch ($action) {
        case 'archive':
            $stmt = $conn->prepare("UPDATE notices SET is_archived=1 WHERE notice_id=?");
            $stmt->bind_param("i", $notice_id);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: admin_dashboard.php"); // Redirect to the dashboard after archiving
                exit();
            } else {
                echo "Failed to archive the notice.";
            }
            break;
        case 'unarchive':
            $stmt = $conn->prepare("UPDATE notices SET is_archived=0 WHERE notice_id=?");
            $stmt->bind_param("i", $notice_id);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: admin_dashboard.php"); // Redirect to the dashboard after unarchiving
                exit();
            } else {
                echo "Failed to unarchive the notice.";
            }
            break;
        case 'edit':
            // This is just a placeholder link, the actual redirection to an edit form would depend on your application setup
            header("Location: admin_notices.php?notice_id=$notice_id");
            exit();
    }
    $stmt->close();
}

$conn->close();
?>
