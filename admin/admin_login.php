<?php
session_name("admin_session");
session_start();
include '../db_connection.php'; // Ensure the correct path to your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_username = $_POST['admin_username'];
    $admin_password = $_POST['admin_password'];

    // Query to fetch admin details
    $query = "SELECT admin.admin_id, admin.admin_password 
              FROM admin 
              WHERE admin.admin_username = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $admin_username); // Bind the username
        $stmt->execute();
        $stmt->bind_result($admin_id, $stored_password);
        $stmt->fetch();
        $stmt->close();

        // Verify password directly (insecure, plain-text comparison)
        if ($admin_id && $admin_password === $stored_password) {
            $_SESSION['admin_id'] = $admin_id; // Set admin session
            header("Location: admin_dashboard.php"); // Redirect to admin dashboard
            exit();
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Database error: Unable to prepare statement.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../css/admin.css"> <!-- Replace with your actual CSS file -->
</head>
<body>

    <main>
    <h1 style="text-align: center; color: #554940;">Admin Login</h1>
        <!-- Specialized Form Class -->
        <form class="admin-login-form" action="admin_login.php" method="POST">
            <?php if (isset($error_message)): ?>
                <p class="admin-error-message"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>
            
            <label for="admin-username" class="admin-login-label">Username:</label>
            <input type="text" id="admin-username" name="admin_username" placeholder="Enter your username" required>
            
            <label for="admin-password" class="admin-login-label">Password:</label>
            <input type="password" id="admin-password" name="admin_password" placeholder="Enter your password" required>
            
            <button type="submit" class="admin-login-button">Login</button>
            
        </form>

        <div class="container">
            <a href="../index.php" class="admin-back-link">Back to Landing Page</a>
        </div>
    </main>
</body>
</html>
