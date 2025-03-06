<?php
session_name("admin_session");
session_start();
include '../db_connection.php';  // Open database connection
include 'admin_sidebar.php';  // Includes the sidebar that assumes an open database connection and session

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : null;
$notice = null;

if ($notice_id) {
    $stmt = $conn->prepare("SELECT * FROM notices WHERE notice_id = ?");
    $stmt->bind_param("i", $notice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notice = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notice_title = $_POST['notice_title'];
    $notice_description = $_POST['notice_description'];
    $upload_dir = "../assets/Notices/";
    $file_path = $notice['file_path'] ?? null;  // Maintain existing path if no new file

    if (!empty($_FILES['notice_file']['name'])) {
        $file_name = basename($_FILES['notice_file']['name']);
        $file_path = $upload_dir . $file_name;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        if (move_uploaded_file($_FILES['notice_file']['tmp_name'], $file_path)) {
            $file_path = $file_path; // Update file path if new file uploaded
        }
    }

    if ($notice_id) {
        // Update the existing notice
        $stmt = $conn->prepare("UPDATE notices SET title = ?, description = ?, file_path = ? WHERE notice_id = ?");
        $stmt->bind_param("sssi", $notice_title, $notice_description, $file_path, $notice_id);
        if ($stmt->execute()) {
            header("Location: admin_dashboard.php"); // Redirect to the dashboard after successful update
            exit();  // Ensure no further execution
        }
        $stmt->close();
    } else {
        // Insert new notice
        $stmt = $conn->prepare("INSERT INTO notices (admin_id, title, description, file_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $admin_id, $notice_title, $notice_description, $file_path);
        if ($stmt->execute()) {
            header("Location: admin_dashboard.php"); // Redirect to the dashboard after successful insert
            exit();  // Ensure no further execution
        }
        $stmt->close();
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $notice_id ? "Edit" : "Add" ?> Important Notice - Stay1B</title>
  <link rel="stylesheet" href="../css/global.css">
</head>
<body>
  <div class="dashboard-container">
    <main>
      <header>
        <h1><?= $notice_id ? "Edit" : "Add" ?> Important Notice</h1>
      </header>
      <form action="" method="POST" enctype="multipart/form-data">
        <label for="notice_title">Notice Title:</label>
        <input type="text" id="notice_title" name="notice_title" required value="<?= htmlspecialchars($notice['title'] ?? '') ?>">

        <label for="notice_description">Notice Description:</label>
        <textarea id="notice_description" name="notice_description"><?= htmlspecialchars($notice['description'] ?? '') ?></textarea>

        <label for="notice_file">Upload File:</label>
        <?php if (!empty($notice['file_path'])): ?>
            <a href="<?= '../assets/Notices/' . htmlspecialchars($notice['file_path']) ?>" target="_blank">View Current File</a><br>
        <?php endif; ?>
        <input type="file" id="notice_file" name="notice_file">

        <button type="submit"><?= $notice_id ? "Update" : "Submit" ?></button>
      </form>

      <?php if (isset($success_message)) echo "<p class='success'>$success_message</p>"; ?>
      <?php if (isset($error_message)) echo "<p class='error'>$error_message</p>"; ?>
    </main>
  </div>
</body>
</html>
