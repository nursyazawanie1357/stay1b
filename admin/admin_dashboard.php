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

// Fetch dashboard statistics
$total_houses = 0;
$pending_maintenance = 0;
$notices = [];

$result_houses = $conn->query("SELECT COUNT(*) AS total FROM landlord_house");
if ($result_houses) {
    $row = $result_houses->fetch_assoc();
    $total_houses = $row['total'];
}

$result_maintenance = $conn->query("SHOW TABLES LIKE 'maintenance_requests'");
if ($result_maintenance->num_rows > 0) {
    $result_maintenance = $conn->query("SELECT COUNT(*) AS pending FROM maintenance_requests WHERE status = 'pending'");
    if ($result_maintenance) {
        $row = $result_maintenance->fetch_assoc();
        $pending_maintenance = $row['pending'];
    }
}

// Fetch notices excluding archived ones
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

// Fetch archived notices
$archived_notices = [];
$result_archived_notices = $conn->query("SELECT notices.*, admin.admin_username 
                                         FROM notices 
                                         JOIN admin ON notices.admin_id = admin.admin_id 
                                         WHERE is_archived = 1 
                                         ORDER BY created_at DESC");
if ($result_archived_notices) {
    while ($row = $result_archived_notices->fetch_assoc()) {
        $archived_notices[] = $row;
    }
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Stay1B</title>
  <link rel="stylesheet" href="../css/global.css">
  <script>
    function toggleArchived() {
        var x = document.getElementById("archivedNotices");
        if (x.style.display === "none") {
            x.style.display = "block";
        } else {
            x.style.display = "none";
        }
    }
  </script>
</head>
<body>
  <div class="dashboard-container">
    <!-- Main Content -->
    <main>
      <header>
        <h1>Admin Dashboard</h1>
      </header>

      <div class="dashboard-summary">
        <h2>Dashboard Overview</h2>
        <p><strong>Total Houses:</strong> <?php echo $total_houses; ?></p>
        <p><strong>Pending Maintenance Requests:</strong> <?php echo $pending_maintenance; ?></p>
      </div>

      <section class="admin-notices-section">
        <h2>Important Notices</h2>
        <div class="notices-container">
          <?php foreach ($notices as $notice): ?>
            <div class="notice-card">
              <h3 class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></h3>
              <p class="notice-description"><?php echo htmlspecialchars($notice['description']); ?></p>
              <?php if (!empty($notice['file_path'])): ?>
                <a href="../assets/Notices/<?php echo htmlspecialchars($notice['file_path']); ?>" target="_blank" style="color: blue;" class="notice-link">View File</a>

              <?php endif; ?>
              <p class="notice-meta">
                <small>Uploaded by: <?php echo htmlspecialchars($notice['admin_username']); ?> on <?php echo htmlspecialchars($notice['created_at']); ?></small>
              </p>
              
              <a href="edit_notice.php?notice_id=<?php echo $notice['notice_id']; ?>&action=edit" style="color:green;">Edit&nbsp;&nbsp;</a>
              <a href="edit_notice.php?notice_id=<?php echo $notice['notice_id']; ?>&action=archive" style="color:red;">Archive</a>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <button onclick="toggleArchived()" style="margin-top: 20px; margin-bottom: 20px;">View Archived Notices</button>


      <section id="archivedNotices" style="display:none;">
  <h2>Archived Notices</h2>
  <div class="notices-container">
    <?php foreach ($archived_notices as $notice): ?>
      <div class="notice-card">
        <h3 class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></h3>
        <p class="notice-description"><?php echo htmlspecialchars($notice['description']); ?></p>
        <?php if (!empty($notice['file_path'])): ?>
          <a href="../assets/Notices/<?php echo htmlspecialchars($notice['file_path']); ?>" target="_blank" class="notice-link">View File</a>
        <?php endif; ?>
        <p class="notice-meta">
          <small>Archived by: <?php echo htmlspecialchars($notice['admin_username']); ?> on <?php echo htmlspecialchars($notice['created_at']); ?></small>
        </p>
        <a href="edit_notice.php?notice_id=<?php echo $notice['notice_id']; ?>&action=unarchive" style="color:green;">Unarchive</a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

    </main>
  </div>
</body>
</html>
