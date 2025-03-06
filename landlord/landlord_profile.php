<?php
session_name("landlord_session");
session_start();
include '../db_connection.php';  // Open database connection
include 'landlord_sidebar.php';  // Includes the sidebar

// Ensure landlord is logged in
if (!isset($_SESSION['landlord_id'])) {
    header("Location: ../landlord_login.php");
    exit();
}

$landlord_id = $_SESSION['landlord_id'];
$landlord_email = '';
$landlord_whatsapp = '';
$landlord_picture = 'assets/profile-pictures/default.png';  // Default picture path

// Fetch current landlord details for initial form values
$stmt = $conn->prepare("SELECT landlord_email, landlord_whatsapp, landlord_picture FROM landlord WHERE landlord_id = ?");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$stmt->bind_result($landlord_email, $landlord_whatsapp, $landlord_picture);
$stmt->fetch();
$stmt->close();

$error_message = '';
$success_message = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = $_POST['landlord_email'] ?? $landlord_email;
    $new_whatsapp = $_POST['landlord_whatsapp'] ?? $landlord_whatsapp;

    // Validate WhatsApp number
    if (!preg_match('/^[0-9]{10,15}$/', $new_whatsapp)) {
        $error_message = "Invalid WhatsApp number. Please enter a valid number.";
    } else {
        // Handle profile picture upload if present
        if (!empty($_FILES['landlord_picture']['name'])) {
            $uploaded_file = $_FILES['landlord_picture'];
            $upload_dir = '../assets/profile-pictures/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid() . "_" . basename($uploaded_file['name']);
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($uploaded_file['tmp_name'], $target_path)) {
                $landlord_picture = 'assets/profile-pictures/' . $file_name;
            } else {
                $error_message = "Failed to upload image.";
            }
        }

        if (!$error_message) {
            // Update landlord details in the database
            $query_update = "UPDATE landlord SET landlord_email = ?, landlord_whatsapp = ?, landlord_picture = ? WHERE landlord_id = ?";
            $stmt = $conn->prepare($query_update);
            $stmt->bind_param("sssi", $new_email, $new_whatsapp, $landlord_picture, $landlord_id);

            if ($stmt->execute()) {
                $success_message = 'Profile updated successfully!';
            } else {
                $error_message = "Error updating profile: " . $conn->error;
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Profile - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/landlord.css">

    <style>
        .profile-edit-form {
            max-width: 600px;
            margin-top: 50px;
            background: #f9f9f9;
            padding: 30px; /* Increased padding for more empty space */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-edit-form label {
            display: block;
            font-weight: bold;
            margin-bottom: 10px;
            
        }

        .profile-edit-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .profile-edit-form button {
            background-color: #5A9A77;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .profile-edit-form button:hover {
            background-color: #497A5E;
        }

        .profile-picture-preview {
            display: block;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 20px auto;
        }

        .success-message {
            color: green;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>


</head>
<body>
    <div class="dashboard-container">
        <main>
            <header>
                <h1>Edit Profile</h1>
            </header>

            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <!-- Profile Edit Form -->
            <form action="landlord_profile.php" method="POST" enctype="multipart/form-data" class="profile-edit-form">
                <img id="profile-picture-preview" class="profile-picture-preview" src="../<?= htmlspecialchars($landlord_picture) ?>" alt="Profile Picture Preview">

                <label for="landlord-email">Email:</label>
                <input type="email" id="landlord-email" name="landlord_email" value="<?= htmlspecialchars($landlord_email) ?>" required>

                <label for="landlord-whatsapp">Contact Number:</label>
                <input type="text" id="landlord-whatsapp" name="landlord_whatsapp" placeholder="Enter contact number (10-15 digits)" value="<?= htmlspecialchars($landlord_whatsapp) ?>" required>

                <label for="landlord-picture">Profile Picture:</label>
                <input type="file" id="landlord-picture" name="landlord_picture" accept="image/*" onchange="previewProfilePicture(event)">

                <button type="submit">Update Profile</button>
            </form>
        </main>
    </div>
</body>
</html>
