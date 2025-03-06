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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $house_number = trim($_POST['house_number'] ?? '');
    $max_occupancy = intval($_POST['max_occupancy'] ?? 0);
    $furnishing_condition = trim($_POST['furnishing_condition'] ?? '');
    $wifi_status = $_POST['wifi_status'] ?? 'Not Available';
    $viewing_date = $_POST['viewing_date'] ?? null;
    $monthly_rental = floatval($_POST['monthly_rental'] ?? 0.0);
    $deposit = floatval($_POST['deposit'] ?? 0.0);

    // Validate inputs
    $errors = [];
    if (empty($house_number)) $errors[] = "House number is required.";
    if ($max_occupancy <= 0) $errors[] = "Max occupancy must be greater than 0.";
    if (!in_array($furnishing_condition, ['Fully Furnished', 'Partially Furnished', 'Not Furnished'])) {
        $errors[] = "Invalid furnishing condition.";
    }
    if ($monthly_rental <= 0) {
        $errors[] = "Monthly rental must be a valid positive number.";
    }
    if ($deposit < 0) {
        $errors[] = "Deposit must be a valid non-negative number.";
    }

    // Default house picture if no file uploaded
    $house_picture = 'assets/house-pictures/house.png';

 // Handle file uploads
if (!empty($_FILES['house_picture']['name'][0])) { // Ensure there is at least one file being uploaded
    $uploaded_files = $_FILES['house_picture'];
    $upload_dir = '../assets/house-pictures/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $picture_paths = [];
    foreach ($uploaded_files['name'] as $index => $file_name) {
        // Check for a valid file name
        if (!empty($file_name)) {
            $temp_path = $uploaded_files['tmp_name'][$index];
            $file_name = uniqid() . "_" . basename($file_name); // Correct use of basename() with a string argument
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($temp_path, $target_path)) {
                $picture_paths[] = 'assets/house-pictures/' . $file_name;
            } else {
                $errors[] = "Failed to move uploaded file.";
            }
        }
    }
    if (!empty($picture_paths)) {
        $house_picture = implode(",", $picture_paths); // Save multiple paths as a comma-separated string
    }
}

    // Proceed if no validation errors
    if (empty($errors)) {
        $query = "INSERT INTO landlord_house (landlord_id, house_number, max_occupancy, furnishing_condition, wifi_status, house_picture, viewing_date, monthly_rental, deposit)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("isissssdd", $landlord_id, $house_number, $max_occupancy, $furnishing_condition, $wifi_status, $house_picture, $viewing_date, $monthly_rental, $deposit);
            if ($stmt->execute()) {
                $last_house_id = $stmt->insert_id;
                // Insert expenses for Monthly Rental and Deposit
                $expense_query = "INSERT INTO expense (house_id, expense_type, expense_amount, due_date, expense_status, receipt_path)
                                  VALUES (?, 'First Month Rental', ?, NULL, 'pending', ''),
                                         (?, 'Deposit', ?, NULL, 'pending', '')";
                $expense_stmt = $conn->prepare($expense_query);
                if ($expense_stmt) {
                    $expense_stmt->bind_param("idid", $last_house_id, $monthly_rental, $last_house_id, $deposit);
                    $expense_stmt->execute();
                    $expense_stmt->close();
                }

                header("Location: landlord_dashboard.php?success=1");
                exit();
            } else {
                $errors[] = "Database error: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $errors[] = "Failed to prepare the database query.";
        }
    }

    // Display errors if any
    if (!empty($errors)) {
        echo "<div class='error-messages'>";
        foreach ($errors as $error) {
            echo "<p>" . htmlspecialchars($error) . "</p>";
        }
        echo "</div>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Listing - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/landlord.css">
</head>
<body>
    <div class="dashboard-container">
        <main>
            <header>
                <h1>Create Listing</h1>
            </header>

            <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form id="create-listing-form" action="create_listing.php" method="POST" enctype="multipart/form-data">
                <label for="house-number">House Number:</label>
                <input type="text" id="house-number" name="house_number" placeholder="Enter house number" required>

                <label for="max-occupancy">Max Occupancy:</label>
                <input type="number" id="max-occupancy" name="max_occupancy" placeholder="Enter max occupants" required>

                <label for="furnishing">Furnishing Condition:</label>
                <select id="furnishing" name="furnishing_condition" required>
                    <option value="">Please select furnishing condition</option>
                    <option value="Fully Furnished">Fully Furnished</option>
                    <option value="Partially Furnished">Partially Furnished</option>
                    <option value="Not Furnished">Not Furnished</option>
                </select>

                <label for="wifi-status">WiFi Status:</label>
                <select id="wifi-status" name="wifi_status" required>
                    <option value="">Please select WiFi status</option>
                    <option value="Available">Available</option>
                    <option value="Not Available">Not Available</option>
                </select>

                <label for="house-picture">Upload Pictures:</label>
                <input type="file" id="house-picture" name="house_picture[]" multiple>

                <label for="viewing-date">Viewing Date:</label>
                <input type="date" id="viewing-date" name="viewing_date" required>

                <label for="monthly-rental">Monthly Rental (RM):</label>
                <input type="number" id="monthly-rental" name="monthly_rental" placeholder="Enter monthly rental" step="0.01" required>

                <label for="deposit">Deposit Amount (RM):</label>
                <input type="number" id="deposit" name="deposit" placeholder="Enter deposit amount" step="0.01" required>

                <button type="submit" class="create-listing-button">Create Listing</button>
            </form>
        </main>
    </div>
</body>
</html>
