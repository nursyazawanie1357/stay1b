<?php
session_name("house_leader_session");
session_start();
include '../db_connection.php'; // Database connection file
include 'leader_sidebar.php'; // Sidebar for leaders

// Check if the house leader is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: leader_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id']; // Leader's housemate ID
$notices = []; // Initialize notices array

// Fetch only unarchived notices
$result_notices = $conn->query("SELECT notices.*, admin.admin_username 
                                FROM notices 
                                JOIN admin ON notices.admin_id = admin.admin_id 
                                WHERE notices.is_archived = 0
                                ORDER BY created_at DESC");
if ($result_notices) {
    while ($row = $result_notices->fetch_assoc()) {
        $notices[] = $row;
    }
} else {
    echo "Error fetching notices: " . $conn->error;
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
        <main>
            <header>
                <h1>Important Notices</h1>
            </header>

            <section class="admin-notices-section">
                <h2>Important Notices</h2>
                <div class="notices-container">
                    <?php if (!empty($notices)): ?>
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
                    <?php else: ?>
                        <p>No active notices are available at the moment.</p>
                    <?php endif; ?>
                </div>
            </section>

        </main>
    </div>
</body>
</html>
