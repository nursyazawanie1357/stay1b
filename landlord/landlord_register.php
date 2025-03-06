<?php
include '../db_connection.php';

// Initialize variables to store field values
$full_name = "";
$username = "";
$email = "";
$whatsapp = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $full_name = htmlspecialchars($_POST['landlord_full_name']);
    $username = htmlspecialchars($_POST['landlord_username']);
    $password = $_POST['landlord_password']; // Raw password for validation
    $email = htmlspecialchars($_POST['landlord_email']);
    $whatsapp = htmlspecialchars($_POST['landlord_whatsapp']);

    // Default profile picture path
    $picture_path = 'assets/profile-pictures/default-profile.png';

    // Password Validation
    $error_message = "";
    if (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error_message = "Password must include at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error_message = "Password must include at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error_message = "Password must include at least one number.";
    } elseif (!preg_match('/[!@#$%^&*()\-_+=\[\]{};:\'",.<>?\/]/', $password)) {
        $error_message = "Password must include at least one special character (e.g., !@#$%^&*).";
    }

    if (empty($error_message)) {
        // If password is valid, hash it
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        // Handle profile picture upload if provided
        if (!empty($_FILES['landlord_picture']['tmp_name'])) {
            $upload_dir = '../assets/profile-pictures/';
            $file_name = uniqid() . '_' . basename($_FILES['landlord_picture']['name']);
            $upload_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['landlord_picture']['tmp_name'], $upload_path)) {
                $picture_path = 'assets/profile-pictures/' . $file_name; // Save uploaded image path
            }
        }

        // Insert data into the database
        $query = "INSERT INTO landlord (landlord_full_name, landlord_username, landlord_password, landlord_email, landlord_whatsapp, landlord_picture)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssss", $full_name, $username, $password_hashed, $email, $whatsapp, $picture_path);

        if ($stmt->execute()) {
            header("Location: landlord_login.php");
            exit();
        } else {
            $error_message = "An error occurred during registration. Please try again.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Landlord Registration - Stay1B</title>
  <link rel="stylesheet" href="../css/landlord.css">
</head>
<body>

  <main>
  <h1 style="text-align: center; color: #554940;">Landlord Register</h1>
    <form class="landlord-registration-form" action="landlord_register.php" method="POST" enctype="multipart/form-data">
      <label for="landlord_full_name" class="landlord-registration-label">Full Name:</label>
      <input type="text" id="landlord_full_name" name="landlord_full_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($full_name); ?>" required>

      <label for="landlord_username" class="landlord-registration-label">Username:</label>
      <input type="text" id="landlord_username" name="landlord_username" placeholder="Enter your username" value="<?php echo htmlspecialchars($username); ?>" required>

      <label for="landlord_password" class="landlord-registration-label">Password:</label>
      <div class="password-container">
        <input type="password" id="landlord_password" name="landlord_password" placeholder="Enter your password" required>
        <span class="info-icon" title="Password Rules">ℹ️</span>
        <div class="info-tooltip">
          <ul>
            <li>At least 8 characters long</li>
            <li>At least one uppercase letter</li>
            <li>At least one lowercase letter</li>
            <li>At least one number</li>
            <li>At least one special character<br>(e.g., !@#$%^&*)</li>
          </ul>
        </div>
      </div>
      <?php if (isset($error_message) && !empty($error_message)) { echo "<p class='error-message'>$error_message</p>"; } ?>

      <label for="landlord_email" class="landlord-registration-label">Email:</label>
      <input type="email" id="landlord_email" name="landlord_email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>

      <label for="landlord_whatsapp" class="landlord-registration-label">Contact Number:</label>
      <input type="text" id="landlord_whatsapp" name="landlord_whatsapp" placeholder="Enter your contact number" value="<?php echo htmlspecialchars($whatsapp); ?>" required>

      <label for="landlord_picture" class="landlord-registration-label">Upload Profile Picture:</label>
      <input type="file" id="landlord_picture" name="landlord_picture" accept="image/*">

      <button type="submit" class="landlord-registration-button">Register</button>

      <p class="form-link" style="text-align: center;">
        Already have an account? <a href="landlord_login.php">Login here</a>
      </p>
    </form>

    <div class="container">
      <a href="../index.php" class="back-to-landing-button">Back to Landing Page</a>
    </div>
  </main>
</body>
</html>
