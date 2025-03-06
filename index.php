<?php
// Include database connection
include 'db_connection.php';

// Initialize notices array
$notices = [];

// Fetch only unarchived notices from the database
$result_notices = $conn->query("SELECT title, description, file_path, created_at FROM notices WHERE is_archived = 0 ORDER BY created_at DESC");
if ($result_notices) {
    while ($row = $result_notices->fetch_assoc()) {
        $notices[] = $row;
    }
} else {
    $error_message = "Unable to fetch notices at the moment.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page</title>
    <link rel="stylesheet" href="css/landing.css">
</head>
<body>
<header class="landing-header">
    <div class="landing-header-content">
        <h1>Student Accommodation Management System</h1>
    </div>

    <div class="user-role-cards">
        <a href="admin/admin_login.php" class="card">
            <div class="role">
                <h2>Admin</h2>
            </div>
        </a>
        <a href="landlord/landlord_login.php" class="card">
            <div class="role">
                <h2>Landlord</h2>
            </div>
        </a>
        <a href="house_leader/leader_login.php" class="card">
            <div class="role">
                <h2>House Leader</h2>
            </div>
        </a>
        <a href="house_member/member_login.php" class="card">
            <div class="role">
                <h2>House Member</h2>
            </div>
        </a>
    </div>

</header>

<main class="landing-main">

    <section class="admin-notices-section">
    <h1 style="text-align: center; color: #554940; font-size: 2rem;">Important Notices</h1>
        <div class="notices-container">
            <?php if (!empty($notices)): ?>
                <?php foreach ($notices as $notice): ?>
                    <div class="notice-card">
                        <h3 class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></h3>
                        <p class="notice-description"><?php echo htmlspecialchars($notice['description']); ?></p>
                        <?php if (!empty($notice['file_path'])): ?>
                            <a href="assets/Notices/<?php echo htmlspecialchars($notice['file_path']); ?>" target="_blank" class="notice-link" style="color: blue; text-decoration: none;">View File</a>
                        <?php endif; ?>
                        <p class="notice-meta">
                            <small>Uploaded on: <?php echo htmlspecialchars($notice['created_at']); ?></small>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No notices are available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
