<?php
session_name("landlord_session");
session_start();
include '../db_connection.php';  // Open database connection
include 'landlord_sidebar.php';  // Includes the sidebar that assumes an open database connection and session

// Check if the landlord is logged in
if (!isset($_SESSION['landlord_id'])) {
    header("Location: landlord_login.php");
    exit();
}

$landlord_id = $_SESSION['landlord_id'];

// Fetch total houses and pending booking requests
$house_query = "SELECT COUNT(*) AS total_houses FROM landlord_house WHERE landlord_id = ?";
$booking_query = "SELECT COUNT(*) AS pending_requests FROM house_booking 
                  JOIN landlord_house ON house_booking.house_id = landlord_house.house_id 
                  WHERE landlord_house.landlord_id = ? AND house_booking.booking_status = 'pending'";

$house_stmt = $conn->prepare($house_query);
$house_stmt->bind_param("i", $landlord_id);
$house_stmt->execute();
$house_stmt->bind_result($total_houses);
$house_stmt->fetch();
$house_stmt->close();

$booking_stmt = $conn->prepare($booking_query);
$booking_stmt->bind_param("i", $landlord_id);
$booking_stmt->execute();
$booking_stmt->bind_result($pending_requests);
$booking_stmt->fetch();
$booking_stmt->close();

// Fetch available houses
$available_houses_query = "SELECT house_id, house_number, max_occupancy, furnishing_condition, wifi_status, house_picture, monthly_rental, deposit, viewing_date 
                           FROM landlord_house WHERE landlord_id = ? AND availability_status = 'Available'";
$available_houses_stmt = $conn->prepare($available_houses_query);
$available_houses_stmt->bind_param("i", $landlord_id);
$available_houses_stmt->execute();
$available_houses_stmt->bind_result($house_id, $house_number, $max_occupancy, $furnishing_condition, $wifi_status, $house_picture, $monthly_rental, $deposit, $viewing_date);
$available_houses = [];
while ($available_houses_stmt->fetch()) {
    $available_houses[] = [
        'house_id' => $house_id,
        'house_number' => $house_number,
        'max_occupancy' => $max_occupancy,
        'furnishing_condition' => $furnishing_condition,
        'wifi_status' => $wifi_status,
        'house_picture' => $house_picture ?: "assets/house-pictures/house.png",
        'monthly_rental' => number_format($monthly_rental, 2),
        'deposit' => number_format($deposit, 2),
        'viewing_date' => $viewing_date,
    ];
}
$available_houses_stmt->close();

// Fetch sold out houses
$sold_out_houses_query = "SELECT house_id, house_number, max_occupancy, furnishing_condition, wifi_status, house_picture, monthly_rental, deposit, viewing_date 
                          FROM landlord_house WHERE landlord_id = ? AND availability_status = 'Sold Out'";
$sold_out_houses_stmt = $conn->prepare($sold_out_houses_query);
$sold_out_houses_stmt->bind_param("i", $landlord_id);
$sold_out_houses_stmt->execute();
$sold_out_houses_stmt->bind_result($house_id, $house_number, $max_occupancy, $furnishing_condition, $wifi_status, $house_picture, $monthly_rental, $deposit, $viewing_date);
$sold_out_houses = [];
while ($sold_out_houses_stmt->fetch()) {
    $sold_out_houses[] = [
        'house_id' => $house_id,
        'house_number' => $house_number,
        'max_occupancy' => $max_occupancy,
        'furnishing_condition' => $furnishing_condition,
        'wifi_status' => $wifi_status,
        'house_picture' => $house_picture ?: "assets/house-pictures/house.png",
        'monthly_rental' => number_format($monthly_rental, 2),
        'deposit' => number_format($deposit, 2),
        'viewing_date' => $viewing_date,
    ];
}
$sold_out_houses_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Landlord Dashboard - Stay1B</title>
  <link rel="stylesheet" href="../css/global.css">
  <style>
      .edit-link {
          color: #28a745;
          font-size: 14px;
          text-decoration: none;
          margin-right: 10px;
      }

      .edit-link:hover {
          color: #28a745;
          text-decoration: underline;
      }

      .delete-link {
          color: #cc2000;
          font-size: 14px;
          text-decoration: none;
      }

      .delete-link:hover {
          color: #cc2000;
          text-decoration: underline;
      }

      .disabled {
          color: gray;
          pointer-events: none;
          cursor: default;
      }
  </style>
</head>

<body>
  <div class="dashboard-container">
    
    <!-- Main Content -->
    <main>
      <header>
        <h1>Landlord Dashboard</h1>
      </header>

      <div class="dashboard-summary">
        <h2>My Properties</h2>
        <p><strong>Total Houses Listed:</strong> <?= $total_houses ?></p>
        <p><strong>Pending Booking Requests:</strong> <?= $pending_requests ?></p>
      </div>

      <div class="house-list">
        <h2>Available Houses</h2>
        <?php if (!empty($available_houses)): ?>
          <div class="houses-container">
            <?php foreach ($available_houses as $house): ?>
              <div class="house-card">
                <img src="../<?= htmlspecialchars($house['house_picture']) ?>" alt="House Picture" class="house-image">
                <div class="house-details">
                  <p><strong>House Number:</strong> <?= htmlspecialchars($house['house_number']) ?></p>
                  <p><strong>Max Occupancy:</strong> <?= htmlspecialchars($house['max_occupancy']) ?></p>
                  <p><strong>Furnishing:</strong> <?= htmlspecialchars($house['furnishing_condition']) ?></p>
                  <p><strong>WiFi:</strong> <?= htmlspecialchars($house['wifi_status']) ?></p>
                  <p><strong>Monthly Rental:</strong> RM <?= htmlspecialchars($house['monthly_rental']) ?></p>
                  <p><strong>Deposit Amount:</strong> RM <?= htmlspecialchars($house['deposit']) ?></p>
                  <p><strong>Viewing Date:</strong> <?= htmlspecialchars($house['viewing_date']) ?></p>
                  <div class="action-buttons">
                    <a href="edit_listing.php?house_id=<?= htmlspecialchars($house['house_id']) ?>" class="edit-link">Edit</a>
                    <a href="delete_listing.php?house_id=<?= htmlspecialchars($house['house_id']) ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this listing?');">Delete</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p>No available houses.</p>
        <?php endif; ?>
      </div>

      <div class="house-list">
        <h2>Sold Out Houses</h2>
        <?php if (!empty($sold_out_houses)): ?>
          <div class="houses-container">
            <?php foreach ($sold_out_houses as $house): ?>
              <div class="house-card">
                <img src="../<?= htmlspecialchars($house['house_picture']) ?>" alt="House Picture" class="house-image">
                <div class="house-details">
                  <p><strong>House Number:</strong> <?= htmlspecialchars($house['house_number']) ?></p>
                  <p><strong>Max Occupancy:</strong> <?= htmlspecialchars($house['max_occupancy']) ?></p>
                  <p><strong>Furnishing:</strong> <?= htmlspecialchars($house['furnishing_condition']) ?></p>
                  <p><strong>WiFi:</strong> <?= htmlspecialchars($house['wifi_status']) ?></p>
                  <p><strong>Monthly Rental:</strong> RM <?= htmlspecialchars($house['monthly_rental']) ?></p>
                  <p><strong>Deposit Amount:</strong> RM <?= htmlspecialchars($house['deposit']) ?></p>
                  <p><strong>Viewing Date:</strong> <?= htmlspecialchars($house['viewing_date']) ?></p>

                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p>No sold out houses.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
