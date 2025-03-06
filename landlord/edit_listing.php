<?php
session_name("landlord_session");
session_start();
include '../db_connection.php';  // Open database connection
include 'landlord_sidebar.php';  // Includes the sidebar that assumes an open database connection and session

// Ensure landlord is logged in
if (!isset($_SESSION['landlord_id'])) {
    header("Location: ../landlord_login.php");
    exit();
}

$landlord_id = $_SESSION['landlord_id'];

// Check if house_id is provided
if (!isset($_GET['house_id'])) {
    header("Location: landlord_dashboard.php?error=House+ID+not+provided");
    exit();
}

$house_id = intval($_GET['house_id']);

// Fetch house details
$query = "SELECT house_number, max_occupancy, furnishing_condition, wifi_status, viewing_date, monthly_rental, deposit, house_picture 
          FROM landlord_house WHERE house_id = ? AND landlord_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $house_id, $landlord_id);
$stmt->execute();
$stmt->bind_result($house_number, $max_occupancy, $furnishing_condition, $wifi_status, $viewing_date, $monthly_rental, $deposit, $house_picture);
if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: landlord_dashboard.php?error=Invalid+House+ID");
    exit();
}
$stmt->close();

if (empty($house_picture)) {
    $house_picture = "assets/house-pictures/default-house.png";
}

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
    if (!is_numeric($monthly_rental) || $monthly_rental <= 0) {
        $errors[] = "Monthly rental must be a valid positive number.";
    }
    if (!is_numeric($deposit) || $deposit < 0) {
        $errors[] = "Deposit must be a valid number.";
    }

    // Handle new house picture upload
    if (!empty($_FILES['house_picture']['name'])) {
        $upload_dir = '../assets/house-pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = uniqid() . "_" . basename($_FILES['house_picture']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['house_picture']['tmp_name'], $target_path)) {
            $house_picture = "assets/house-pictures/" . $file_name;
        } else {
            $errors[] = "Failed to upload house picture.";
        }
    }

    // Proceed if no validation errors
    if (empty($errors)) {
        $query = "UPDATE landlord_house 
                  SET house_number = ?, max_occupancy = ?, furnishing_condition = ?, wifi_status = ?, viewing_date = ?, monthly_rental = ?, deposit = ?, house_picture = ?
                  WHERE house_id = ? AND landlord_id = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param(
                "sisssddsii",
                $house_number,
                $max_occupancy,
                $furnishing_condition,
                $wifi_status,
                $viewing_date,
                $monthly_rental,
                $deposit,
                $house_picture,
                $house_id,
                $landlord_id
            );

            if ($stmt->execute()) {
                header("Location: landlord_dashboard.php?success=House+updated+successfully");
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
  <title>Edit Listing - Stay1B</title>
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/landlord.css">
</head>
<body>
  <div class="dashboard-container">

    <main>
      <header>
        <h1>Edit Listing</h1>
      </header>

      <form id="edit-listing-form" action="edit_listing.php?house_id=<?= $house_id ?>" method="POST" enctype="multipart/form-data">
        <label for="house-number">House Number:</label>
        <input type="text" id="house-number" name="house_number" value="<?= htmlspecialchars($house_number) ?>" required>
        
        <label for="max-occupancy">Max Occupancy:</label>
        <input type="number" id="max-occupancy" name="max_occupancy" value="<?= htmlspecialchars($max_occupancy) ?>" required>
        
        <label for="furnishing">Furnishing Condition:</label>
        <select id="furnishing" name="furnishing_condition" required>
            <option value="Fully Furnished" <?= $furnishing_condition === "Fully Furnished" ? "selected" : "" ?>>Fully Furnished</option>
            <option value="Partially Furnished" <?= $furnishing_condition === "Partially Furnished" ? "selected" : "" ?>>Partially Furnished</option>
            <option value="Not Furnished" <?= $furnishing_condition === "Not Furnished" ? "selected" : "" ?>>Not Furnished</option>
        </select>

        <label for="wifi-status">WiFi Status:</label>
        <select id="wifi-status" name="wifi_status" required>
            <option value="Available" <?= $wifi_status === "Available" ? "selected" : "" ?>>Available</option>
            <option value="Not Available" <?= $wifi_status === "Not Available" ? "selected" : "" ?>>Not Available</option>
        </select>
        
        <label for="house-picture">House Picture:</label>
        <input type="file" id="house-picture" name="house_picture" accept="image/*">

        <label for="viewing-date">Viewing Date:</label>
        <input type="date" id="viewing-date" name="viewing_date" value="<?= htmlspecialchars($viewing_date) ?>" required>
        
        <label for="monthly-rental">Monthly Rental (RM):</label>
        <input type="number" id="monthly-rental" name="monthly_rental" value="<?= htmlspecialchars($monthly_rental) ?>" step="0.01" required>
        
        <label for="deposit">Deposit Amount (RM):</label>
        <input type="number" id="deposit" name="deposit" value="<?= htmlspecialchars($deposit) ?>" step="0.01" required>
        
        <button type="submit" class="edit-listing-button">Update Listing</button>
      </form>
    </main>
  </div>
</body>
</html>
