<?php
session_name("landlord_session");
session_start();
include '../db_connection.php';  // Open database connection
include 'landlord_sidebar.php';  // Includes the sidebar that assumes an open database connection and session

// Check if the landlord is logged in
if (!isset($_SESSION['landlord_id'])) {
    header("Location: landlord_login.php");
    exit();
}

$landlord_id = $_SESSION['landlord_id'];
$notices = [];

// Fetch only unarchived notices
$result_notices = $conn->query("SELECT notices.*, admin.admin_username 
                                FROM notices 
                                JOIN admin ON notices.admin_id = admin.admin_id 
                                WHERE is_archived = 0 
                                ORDER BY created_at DESC");
if ($result_notices) {
    while ($row = $result_notices->fetch_assoc()) {
        $notices[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Important Notices - Stay1B</title>
  <link rel="stylesheet" href="../css/global.css">
</head>
<body>
  <div class="dashboard-container">
    <!-- Main Content -->
    <main>
      <header>
        <h1>Important Notices</h1>
      </header>

      <section class="admin-notices-section">
        <h2>Important Notices</h2>
        <div class="notices-container">
          <?php foreach ($notices as $notice): ?>
            <div class="notice-card">
              <h3 class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></h3>
              <p class="notice-description"><?php echo htmlspecialchars($notice['description']); ?></p>
              <?php if (!empty($notice['file_path'])): ?>
                <a href="../assets/Notices/<?php echo htmlspecialchars($notice['file_path']); ?>" target="_blank" style="color: blue;">View File</a>
              <?php endif; ?>
              <p class="notice-meta">
                <small>Uploaded by: <?php echo htmlspecialchars($notice['admin_username']); ?> on <?php echo htmlspecialchars($notice['created_at']); ?></small>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

    </main>
  </div>
</body>
</html>
