<?php
session_name("house_leader_session");
session_start();
include '../db_connection.php';
include 'leader_sidebar.php'; // Including the sidebar from the same directory

// Check if the tenant leader is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: leader_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id']; // Tenant leader's housemate ID

// Fetch all houses listed by landlords along with landlord's Contact number, filtering by availability
$query_houses = "SELECT lh.house_id, lh.house_number, lh.max_occupancy, lh.furnishing_condition, 
                        lh.wifi_status, lh.house_picture, lh.monthly_rental, lh.deposit, 
                        lh.viewing_date, lh.availability_status, l.landlord_whatsapp 
                 FROM landlord_house lh
                 JOIN landlord l ON lh.landlord_id = l.landlord_id
                 WHERE lh.availability_status = 'Available'";
$result_houses = $conn->query($query_houses);
$houses = [];
while ($row = $result_houses->fetch_assoc()) {
    $row['monthly_rental'] = number_format($row['monthly_rental'], 2);
    $row['deposit'] = number_format($row['deposit'], 2);
    $row['house_picture'] = $row['house_picture'] ?: "assets/house-pictures/default-house.png";
    $houses[] = $row;
}

// Check for existing booking
$query_existing_booking = "SELECT house_id FROM house_booking 
                           WHERE housemate_id = ? AND booking_status IN ('pending', 'approved')";
$stmt_existing_booking = $conn->prepare($query_existing_booking);
$stmt_existing_booking->bind_param("i", $housemate_id);
$stmt_existing_booking->execute();
$stmt_existing_booking->bind_result($existing_booking_house_id);
$stmt_existing_booking->fetch();
$stmt_existing_booking->close();

// Process booking request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['book_house_id'])) {
    if ($existing_booking_house_id) {
        $_SESSION['error_message'] = "You already have an active booking.";
    } else {
        $selected_house_id = (int)$_GET['book_house_id'];

        $query_booking = "INSERT INTO house_booking (house_id, housemate_id, booking_status) 
                          VALUES (?, ?, 'pending')";
        $stmt_booking = $conn->prepare($query_booking);
        $stmt_booking->bind_param("ii", $selected_house_id, $housemate_id);

        if ($stmt_booking->execute()) {
            $_SESSION['success_message'] = "Your booking request has been sent to the landlord!";
        } else {
            $_SESSION['error_message'] = "Failed to send booking request. Please try again.";
        }
        $stmt_booking->close();
    }
    header("Location: house_listings.php");
    exit();
}

// Cancel booking request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['cancel_booking_id'])) {
    $cancel_house_id = (int)$_GET['cancel_booking_id'];

    $query_cancel_booking = "DELETE FROM house_booking WHERE house_id = ? AND housemate_id = ?";
    $stmt_cancel_booking = $conn->prepare($query_cancel_booking);
    $stmt_cancel_booking->bind_param("ii", $cancel_house_id, $housemate_id);

    if ($stmt_cancel_booking->execute()) {
        $_SESSION['success_message'] = "Your booking has been successfully canceled.";
    } else {
        $_SESSION['error_message'] = "Failed to cancel your booking. Please try again.";
    }
    $stmt_cancel_booking->close();

    header("Location: house_listings.php");
    exit();
}

// Fetch booking statuses for the current housemate
$query_booking_status = "SELECT house_booking.house_id, house_booking.booking_status 
                         FROM house_booking 
                         WHERE house_booking.housemate_id = ?";
$stmt_booking_status = $conn->prepare($query_booking_status);
$stmt_booking_status->bind_param("i", $housemate_id);
$stmt_booking_status->execute();
$result_booking_status = $stmt_booking_status->get_result();
$booking_statuses = [];
while ($row = $result_booking_status->fetch_assoc()) {
    $booking_statuses[$row['house_id']] = $row['booking_status'];
}
$stmt_booking_status->close();

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Listings - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <style>
        .book-link {
            color: #28a745; /* Green */
            font-size: 14px;
            text-decoration: none;
            margin-right: 10px;
        }

        .book-link:hover {
            color: #28a745;
            text-decoration: underline;
        }

        .cancel-link {
            color: #cc2000 ; /* Red */
            font-size: 14px;
            text-decoration: none;
            margin-right: 10px;
        }

        .cancel-link:hover {
            color: #cc2000 ;
            text-decoration: underline;
        }

        .whatsapp-link {
            color: #0000FF; /* Yellow */
            font-size: 14px;
            text-decoration: none;
        }

        .whatsapp-link:hover {
            color: #0000FF;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">

        <!-- Main Content -->
        <main>
            <header>
                <h1>House Listings</h1>
            </header>

            <div class="house-list">
                <h2>Available Houses</h2>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="success-message"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php elseif (isset($_SESSION['error_message'])): ?>
                    <div class="error-message"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <?php if (!empty($houses)): ?>
                    <div class="houses-container">
                        <?php foreach ($houses as $listed_house): ?>
                            <div class="house-card">
                                <img src="../<?= htmlspecialchars($listed_house['house_picture']) ?>" alt="House Picture" class="house-image">
                                <div class="house-details">
                                    <p><strong>House Number:</strong> <?= htmlspecialchars($listed_house['house_number']) ?></p>
                                    <p><strong>Max Occupancy:</strong> <?= htmlspecialchars($listed_house['max_occupancy']) ?></p>
                                    <p><strong>Furnishing:</strong> <?= htmlspecialchars($listed_house['furnishing_condition']) ?></p>
                                    <p><strong>WiFi:</strong> <?= htmlspecialchars($listed_house['wifi_status']) ?></p>
                                    <p><strong>Monthly Rental:</strong> RM <?= htmlspecialchars($listed_house['monthly_rental']) ?></p>
                                    <p><strong>Deposit:</strong> RM <?= htmlspecialchars($listed_house['deposit']) ?></p>
                                    <p><strong>Viewing Date:</strong> <?= htmlspecialchars($listed_house['viewing_date']) ?></p>

                                    <!-- Booking Status Message -->
                                    <?php if (isset($booking_statuses[$listed_house['house_id']]) && $booking_statuses[$listed_house['house_id']] === 'approved'): ?>
                                        <p style="color: green; font-weight: bold;">Your booking has been confirmed.</p>
                                    <?php elseif (isset($booking_statuses[$listed_house['house_id']]) && $booking_statuses[$listed_house['house_id']] === 'pending'): ?>
                                        <p style="color: orange; font-weight: bold;">Your booking request is pending.</p>
                                    <?php elseif (isset($booking_statuses[$listed_house['house_id']]) && $booking_statuses[$listed_house['house_id']] === 'rejected'): ?>
                                        <p style="color: red; font-weight: bold;">Your booking was rejected. Please contact the landlord.</p>
                                    <?php endif; ?>

                                    <!-- Buttons -->
                                    <div class="button-container">
                                        <?php if (!isset($booking_statuses[$listed_house['house_id']])): ?>
                                            <a href="?book_house_id=<?= htmlspecialchars($listed_house['house_id']) ?>" class="book-link">Book House</a>
                                        <?php elseif ($booking_statuses[$listed_house['house_id']] === 'pending'): ?>
                                            <a href="?cancel_booking_id=<?= htmlspecialchars($listed_house['house_id']) ?>" class="cancel-link">Cancel Booking</a>
                                        <?php endif; ?>
                                        <a href="https://wa.me/<?= htmlspecialchars($listed_house['landlord_whatsapp']) ?>" class="whatsapp-link" target="_blank">Contact Landlord</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No houses available at the moment. Please check back later.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
