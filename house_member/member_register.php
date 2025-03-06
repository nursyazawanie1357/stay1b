<?php
include '../db_connection.php';

// Initialize variables to store field values
$full_name = "";
$username = "";
$email = "";
$whatsapp = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $full_name = htmlspecialchars($_POST['tenant_full_name']);
    $username = htmlspecialchars($_POST['tenant_username']);
    $password = $_POST['tenant_password']; // Raw password for validation
    $email = htmlspecialchars($_POST['tenant_email']);
    $whatsapp = htmlspecialchars($_POST['tenant_whatsapp']);

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
        if (!empty($_FILES['tenant_picture']['tmp_name'])) {
            $upload_dir = '../assets/profile-pictures/';
            $file_name = uniqid() . '_' . basename($_FILES['tenant_picture']['name']);
            $upload_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['tenant_picture']['tmp_name'], $upload_path)) {
                $picture_path = 'assets/profile-pictures/' . $file_name; // Save uploaded image path
            }
        }

        // Insert tenant details into the `tenant` table
        $query_tenant = "INSERT INTO tenant (tenant_full_name, tenant_username, tenant_password, tenant_email, tenant_whatsapp, tenant_picture)
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_tenant = $conn->prepare($query_tenant);
        $stmt_tenant->bind_param("ssssss", $full_name, $username, $password_hashed, $email, $whatsapp, $picture_path);

        if ($stmt_tenant->execute()) {
            // Get the newly inserted tenant_id
            $tenant_id = $stmt_tenant->insert_id;

            // Insert into `housemate_role` table with 'member' role
            $query_role = "INSERT INTO housemate_role (tenant_id, house_role) VALUES (?, 'member')";
            $stmt_role = $conn->prepare($query_role);
            $stmt_role->bind_param("i", $tenant_id);

            if ($stmt_role->execute()) {
                header("Location: member_login.php");
                exit();
            } else {
                $error_message = "An error occurred while assigning the member role.";
            }
        } else {
            $error_message = "An error occurred during registration. Please try again.";
        }

        $stmt_tenant->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>House Member Registration - Stay1B</title>
  <link rel="stylesheet" href="../css/member.css">
</head>
<body>
  <main>
  <h1 style="text-align: center; color: #554940;">House Member Register</h1>
    <form class="member-registration-form" action="member_register.php" method="POST" enctype="multipart/form-data">
      <label for="tenant_full_name" class="member-registration-label">Full Name:</label>
      <input type="text" id="tenant_full_name" name="tenant_full_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($full_name); ?>" required>

      <label for="tenant_username" class="member-registration-label">Username:</label>
      <input type="text" id="tenant_username" name="tenant_username" placeholder="Enter your username" value="<?php echo htmlspecialchars($username); ?>" required>

      <label for="tenant_password" class="member-registration-label">Password:</label>
      <div class="password-container">
        <input type="password" id="tenant_password" name="tenant_password" placeholder="Enter your password" required>
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

      <label for="tenant_email" class="member-registration-label">Email:</label>
      <input type="email" id="tenant_email" name="tenant_email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>

      <label for="tenant_whatsapp" class="member-registration-label">Contact:</label>
      <input type="text" id="tenant_whatsapp" name="tenant_whatsapp" placeholder="Enter your contact number" value="<?php echo htmlspecialchars($whatsapp); ?>" required>

      <label for="tenant_picture" class="member-registration-label">Upload Profile Picture:</label>
      <input type="file" id="tenant_picture" name="tenant_picture" accept="image/*">

      <button type="submit" class="member-registration-button">Register</button>

      <p class="form-link" style="text-align: center;">
        Already have an account? <a href="member_login.php">Login here</a>
      </p>
    </form>

    <div class="container">
      <a href="../index.php" class="back-to-landing-button">Back to Landing Page</a>
    </div>
  </main>
</body>
</html>
