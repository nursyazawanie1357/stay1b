<! –– landlord_login.php ––>

<?php
session_name("landlord_session");
session_start();
include '../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['landlord_username'];
    $password = $_POST['landlord_password'];

    // Prepare and execute query to fetch user details
    $query = "SELECT landlord_id, landlord_password FROM landlord WHERE landlord_username = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($landlord_id, $hashed_password);
        $stmt->fetch();
        $stmt->close();

        // Check if user exists and verify password
        if ($landlord_id && password_verify($password, $hashed_password)) {
            $_SESSION['landlord_id'] = $landlord_id; // Set session
            header("Location: landlord_dashboard.php"); // Redirect to dashboard
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
  <title>Landlord Login - Stay1B</title>

  <link rel="stylesheet" href="../css/landlord.css">
</head>
<body>
  <main>
  <h1 style="text-align: center; color: #554940;">Landlord Login</h1>
    <!-- Specialized Login Form -->
    <form class="landlord-login-form" action="landlord_login.php" method="POST">
      <?php if (isset($error_message)): ?>
        <p class="error-message" style="color: red; text-align: center;"><?= htmlspecialchars($error_message) ?></p>
      <?php endif; ?>
      
      <label for="username" class="landlord-login-label">Username:</label>
      <input type="text" id="username" name="landlord_username" placeholder="Enter your username" required>
      
      <label for="password" class="landlord-login-label">Password:</label>
      <input type="password" id="password" name="landlord_password" placeholder="Enter your password" required>
      
      <button type="submit" class="landlord-login-button">Login</button>
      
      <!-- Link to Register -->
      <p class="form-link" style="text-align: center;">
          Don't have an account? <a href="landlord_register.php">Register here</a>
      </p>
    </form>
    
<div class="container">
<a href="../index.php" class="back-to-landing-button">Back to Landing Page</a>
</div>



  </main>
</body>
</html>

