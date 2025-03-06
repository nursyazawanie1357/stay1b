<?php
session_name("house_member_session");
session_start();
include '../db_connection.php';
include 'member_sidebar.php'; // Include the member sidebar PHP file

// Check if the member is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: member.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id'];

// Fetch member details
$query_member = "SELECT tenant.tenant_full_name, tenant.tenant_email, tenant.tenant_whatsapp, tenant.tenant_picture 
                 FROM housemate_role
                 JOIN tenant ON housemate_role.tenant_id = tenant.tenant_id
                 WHERE housemate_role.housemate_id = ?";
$stmt = $conn->prepare($query_member);
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$stmt->bind_result($member_full_name, $member_email, $member_whatsapp, $member_picture);
$stmt->fetch();
$stmt->close();

// Use default profile picture if none is uploaded
if (empty($member_picture)) {
    $member_picture = "assets/profile-pictures/default-profile.png";
}

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = $_POST['tenant_email'];
    $new_whatsapp = $_POST['tenant_whatsapp'];

    // Validate WhatsApp number
    if (!preg_match('/^[0-9]{10,15}$/', $new_whatsapp)) {
        $error_message = "Invalid WhatsApp number. Please enter a valid number.";
    } else {
        // Handle profile picture upload
        if (!empty($_FILES['tenant_picture']['name'])) {
            $uploaded_file = $_FILES['tenant_picture'];
            $upload_dir = '../assets/profile-pictures/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid() . "_" . basename($uploaded_file['name']);
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($uploaded_file['tmp_name'], $target_path)) {
                $member_picture = 'assets/profile-pictures/' . $file_name;
            }
        }

        // Update member details in the database
        $query_update = "UPDATE tenant SET tenant_email = ?, tenant_whatsapp = ?, tenant_picture = ? WHERE tenant_id = (SELECT tenant_id FROM housemate_role WHERE housemate_id = ?)";
        $stmt = $conn->prepare($query_update);
        $stmt->bind_param("sssi", $new_email, $new_whatsapp, $member_picture, $housemate_id);

        if ($stmt->execute()) {
            header("Location: member_profile.php?success=1");
            exit();
        } else {
            $error_message = "Error: " . $conn->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/member.css">
    <style>
        .profile-edit-form {
            max-width: 600px;
            margin-top: 50px;
            background: #f9f9f9;
            padding: 30px;
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
    <script>
        // Live preview of uploaded profile picture
        function previewProfilePicture(event) {
            const reader = new FileReader();
            reader.onload = function () {
                const output = document.getElementById('profile-picture-preview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->

        <!-- Main Content -->
        <main>
            <header>
                <h1>Edit Profile</h1>
            </header>

            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="success-message">Profile updated successfully!</div>
            <?php elseif (!empty($error_message)): ?>
                <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <!-- Profile Edit Form -->
            <form action="member_profile.php" method="POST" enctype="multipart/form-data" class="profile-edit-form">
                <img id="profile-picture-preview" class="profile-picture-preview" src="../<?= htmlspecialchars($member_picture) ?>" alt="Profile Picture Preview">

                <label for="tenant-email">Email:</label>
                <input type="email" id="tenant-email" name="tenant_email" value="<?= htmlspecialchars($member_email) ?>" required>

                <label for="tenant-whatsapp">Contact Number:</label>
                <input type="text" id="tenant-whatsapp" name="tenant_whatsapp" placeholder="Enter contact number (10-15 digits)" value="<?= htmlspecialchars($member_whatsapp) ?>" required>

                <label for="tenant-picture">Profile Picture:</label>
                <input type="file" id="tenant-picture" name="tenant_picture" accept="image/*" onchange="previewProfilePicture(event)">

                <button type="submit">Update Profile</button>
            </form>
        </main>
    </div>
</body>
</html>
