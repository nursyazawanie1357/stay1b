<?php
session_name("house_member_session");
session_start();
include '../db_connection.php'; // Include the database connection file
include 'member_sidebar.php'; // Include the sidebar for house members

// Check if the house member is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: member_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id']; // Current house member's ID

// Check if the housemate ID exists in housemate_role
$check_housemate_query = "SELECT COUNT(*) AS count FROM housemate_role WHERE housemate_id = ?";
$stmt_check = $conn->prepare($check_housemate_query);
$stmt_check->bind_param("i", $housemate_id);
$stmt_check->execute();
$stmt_check->bind_result($exists);
$stmt_check->fetch();
$stmt_check->close();

if ($exists == 0) {
    $_SESSION['message'] = "Invalid housemate ID. Please contact support.";
    header("Location: member_login.php");
    exit();
}

// Handle house application
if (isset($_GET['apply_house_id'])) {
    $apply_house_id = intval($_GET['apply_house_id']); // Sanitize the input

    // Check if the house ID is linked to a valid booking
    $check_booking_query = "
        SELECT booked_house_id 
        FROM house_booking 
        WHERE house_id = ? AND booking_status = 'approved'
    ";
    $stmt_booking = $conn->prepare($check_booking_query);
    $stmt_booking->bind_param("i", $apply_house_id);
    $stmt_booking->execute();
    $stmt_booking->bind_result($booked_house_id);
    $stmt_booking->fetch();
    $stmt_booking->close();

    if (empty($booked_house_id)) {
        $_SESSION['message'] = "Invalid house ID or no approved booking found.";
        header("Location: house_finder.php");
        exit();
    }

    // Insert the application into housemate_application
    $stmt = $conn->prepare("
        INSERT INTO housemate_application (booked_house_id, housemate_id, application_status) 
        VALUES (?, ?, 'pending')
    ");
    $stmt->bind_param("ii", $booked_house_id, $housemate_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Application submitted successfully!";
    } else {
        $_SESSION['message'] = "Failed to submit application. Error: " . $stmt->error;
    }
    $stmt->close();
    header("Location: house_finder.php");
    exit();
}

// Handle application cancellation
if (isset($_GET['cancel_application_id'])) {
    $cancel_application_id = intval($_GET['cancel_application_id']); // Sanitize the input

    // Delete the application
    $stmt = $conn->prepare("
        DELETE hf 
        FROM housemate_application hf
        JOIN house_booking hb ON hf.booked_house_id = hb.booked_house_id
        WHERE hf.housemate_id = ? AND hb.house_id = ?
    ");
    $stmt->bind_param("ii", $housemate_id, $cancel_application_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Application cancelled successfully!";
    } else {
        $_SESSION['message'] = "Failed to cancel application. Error: " . $stmt->error;
    }
    $stmt->close();
    header("Location: house_finder.php");
    exit();
}

// Fetch available houses and application statuses
$query = "
    SELECT 
        lh.house_id,
        lh.house_number,
        lh.max_occupancy,
        lh.furnishing_condition,
        lh.wifi_status,
        lh.monthly_rental,
        lh.deposit,
        lh.house_picture,
        COALESCE(hp.gender_preference, 'Any') AS gender_preference,
        COALESCE(hp.study_year_preference, 'Any') AS study_year_preference,
        COALESCE(hp.pet_policy_preference, 'No Pets') AS pet_policy_preference,
        hf.application_status
    FROM house_booking hb
    JOIN landlord_house lh ON hb.house_id = lh.house_id
    LEFT JOIN house_preferences hp ON hb.booked_house_id = hp.booked_house_id
    LEFT JOIN housemate_application hf ON hf.booked_house_id = hb.booked_house_id AND hf.housemate_id = ?
    WHERE hb.booking_status = 'approved'
    GROUP BY lh.house_id
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$result = $stmt->get_result();

$houses = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['house_picture'] = !empty($row['house_picture']) 
            ? htmlspecialchars($row['house_picture']) 
            : "assets/house-pictures/default-house.png";
        $houses[] = $row;
    }
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Finder - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <style>
        .apply-link {
            color: blue;
            font-size: 14px;
            text-decoration: none;
            margin-right: 10px;
        }
        .apply-link:hover {
            text-decoration: underline;
        }
        .cancel-link {
            color: red;
            font-size: 14px;
            text-decoration: none;
        }
        .cancel-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <main>
            <header>
                <h1>House Finder</h1>
            </header>

            <section class="house-list">
                <h2>Available Houses</h2>
                <?php if (!empty($houses)): ?>
                    <div class="houses-container">
                        <?php foreach ($houses as $house): ?>
                            <div class="house-card">
                                <img src="../<?= htmlspecialchars($house['house_picture']) ?>" alt="House Picture" class="house-image">
                                <div class="house-details">
                                    <p><strong>House Number:</strong> <?= htmlspecialchars($house['house_number']) ?></p>
                                    <p><strong>Max Occupancy:</strong> <?= htmlspecialchars($house['max_occupancy']) ?></p>
                                    <p><strong>Furnishing:</strong> <?= htmlspecialchars($house['furnishing_condition']) ?></p>
                                    <p><strong>WiFi:</strong> <?= htmlspecialchars($house['wifi_status']) ?></p>
                                    <p><strong>Monthly Rental:</strong> RM <?= number_format($house['monthly_rental'], 2) ?></p>
                                    <p><strong>Deposit:</strong> RM <?= number_format($house['deposit'], 2) ?></p>
                                    <p><strong>Gender Preference:</strong> <?= htmlspecialchars($house['gender_preference']) ?></p>
                                    <p><strong>Study Year Preference:</strong> <?= htmlspecialchars($house['study_year_preference']) ?></p>
                                    <p><strong>Pet Policy:</strong> <?= htmlspecialchars($house['pet_policy_preference']) ?></p>

                                    <!-- Application Status Message -->
                                    <?php if ($house['application_status'] === 'approved'): ?>
                                        <p style="color: green; font-weight: bold;">Your application has been approved.</p>
                                    <?php elseif ($house['application_status'] === 'pending'): ?>
                                        <p style="color: orange; font-weight: bold;">Your application is pending.</p>
                                    <?php elseif ($house['application_status'] === 'rejected'): ?>
                                        <p style="color: red; font-weight: bold;">Your application was rejected.</p>
                                    <?php endif; ?>

                                    <div class="button-container">
                                        <?php if (empty($house['application_status'])): ?>
                                            <a href="?apply_house_id=<?= htmlspecialchars($house['house_id']) ?>" class="apply-link">Apply as house member</a>
                                        <?php elseif ($house['application_status'] === 'pending'): ?>
                                            <a href="?cancel_application_id=<?= htmlspecialchars($house['house_id']) ?>" class="cancel-link">Cancel Application</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No houses available at the moment. Please check back later.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
