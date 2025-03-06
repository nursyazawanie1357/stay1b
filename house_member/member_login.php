<?php
session_name("house_member_session");
session_start();
include '../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['tenant_username'];
    $password = $_POST['tenant_password'];

    // Query to fetch house member details (with house_role = 'member')
    $query = "SELECT tenant.tenant_id, tenant.tenant_password, housemate_role.housemate_id 
              FROM tenant 
              JOIN housemate_role ON tenant.tenant_id = housemate_role.tenant_id 
              WHERE tenant.tenant_username = ? AND housemate_role.house_role = 'member'";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $username); // Bind the username
        $stmt->execute();
        $stmt->bind_result($tenant_id, $hashed_password, $housemate_id);
        $stmt->fetch();
        $stmt->close();

        // Check password and set session if valid
        if ($tenant_id && password_verify($password, $hashed_password)) {
            $_SESSION['housemate_id'] = $housemate_id; // Set session for house member
            header("Location: member_dashboard.php"); // Redirect to house member dashboard
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
    <title>House Member Login</title>
    <link rel="stylesheet" href="../css/member.css">
</head>
<body>
    <main>
    <h1 style="text-align: center; color: #554940;">House Member Login</h1>
        <!-- Specialized Form Class -->
        <form class="member-login-form" action="member_login.php" method="POST">
            <?php if (isset($error_message)): ?>
                <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>
            
            <label for="username" class="member-login-label">Username:</label>
            <input type="text" id="username" name="tenant_username" placeholder="Enter your username" required>
            
            <label for="password" class="member-login-label">Password:</label>
            <input type="password" id="password" name="tenant_password" placeholder="Enter your password" required>
            
            <button type="submit" class="member-login-button">Login</button>
            
            <p class="form-link" style="text-align: center;">
                Don't have an account? <a href="member_register.php">Register here</a>
            </p>
        </form>

        <div class="container">
            <a href="../index.php" class="back-to-landing-button">Back to Landing Page</a>
        </div>
    </main>
</body>
</html>
