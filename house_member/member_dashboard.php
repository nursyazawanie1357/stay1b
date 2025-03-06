<?php
session_name("house_member_session");
session_start();
include '../db_connection.php'; // Adjust the path if necessary
include 'member_sidebar.php'; // Sidebar for members

// Check if the member is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: member_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id'];

// Fetch house details specifically for the logged-in user
$query_house = "
    SELECT lh.house_number, lh.max_occupancy, lh.furnishing_condition, lh.wifi_status, lh.house_picture, 
           lh.monthly_rental, lh.deposit 
    FROM house_group hg
    JOIN house_booking hb ON hg.booked_house_id = hb.booked_house_id
    JOIN landlord_house lh ON hb.house_id = lh.house_id
    WHERE hg.housemate_id = ?";
$stmt = $conn->prepare($query_house);
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$result = $stmt->get_result();
$house = $result->fetch_assoc();
$stmt->close();

// Fetch house leader and members
$query_leader_members = "
    SELECT hr.house_role, t.tenant_full_name 
    FROM house_group hg
    JOIN house_booking hb ON hg.booked_house_id = hb.booked_house_id
    JOIN housemate_role hr ON hg.housemate_id = hr.housemate_id
    JOIN tenant t ON hr.tenant_id = t.tenant_id
    WHERE hb.booked_house_id = (
        SELECT booked_house_id
        FROM house_group
        WHERE housemate_id = ?
    )
    ORDER BY hr.house_role DESC, t.tenant_full_name";
$stmt = $conn->prepare($query_leader_members);
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$result = $stmt->get_result();

$houseDetails = [
    'leader' => null,
    'members' => []
];

while ($row = $result->fetch_assoc()) {
    if ($row['house_role'] === 'leader') {
        $houseDetails['leader'] = $row['tenant_full_name'];
    } elseif ($row['house_role'] === 'member') {
        $houseDetails['members'][] = $row['tenant_full_name'];
    }
}
$stmt->close();

// Fetch current preferences
$query_preferences = "
    SELECT gender_preference, study_year_preference, pet_policy_preference 
    FROM house_preferences 
    WHERE booked_house_id = (
        SELECT booked_house_id 
        FROM house_group 
        WHERE housemate_id = ?
    )";
$stmt = $conn->prepare($query_preferences);
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$stmt->bind_result($gender_preference, $study_year_preference, $pet_policy_preference);
$preferences_exist = $stmt->fetch();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <style>
        .dashboard-container {
            display: flex;
            justify-content: space-between;
        }
        .dashboard-section {
            width: 48%;
        }
        .section-container {
            background: white;
            border: 1px solid #C5C6C7;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            padding: 15px; /* Adds some padding for visual spacing */
            margin-bottom: 20px; /* Adds margin between sections */
        }
        .section-title {
            margin-bottom: 10px; /* Adds margin below the title for spacing */
        }
        .my-house-title {
            margin-left: 10px; /* Moves the "My House" title to the right */
        }
    </style>
</head>
<body>
    <div class="member-dashboard-wrapper">
        <!-- Main Content -->
        <main>
            <header>
                <h1>Member Dashboard</h1>
            </header>

            <div class="dashboard-container">
                <!-- My House Section -->
                <div class="dashboard-section">
                    <h2 class="my-house-title">My House</h2>
                    <?php if (!empty($house)): ?>
                        <div class="member-house-card">
                            <!-- House Image -->
                            <img src="../<?= htmlspecialchars($house['house_picture']) ?>" alt="House Picture" class="member-house-image">
                            
                            <!-- House Details -->
                            <div class="member-house-details">
                                <p><strong>House Number:</strong> <?= htmlspecialchars($house['house_number']) ?></p>
                                <p><strong>House Leader:</strong> <?= htmlspecialchars($houseDetails['leader']) ?></p>
                                <p><strong>House Members:</strong></p>
                                <ul class="member-list">
                                    <?php foreach ($houseDetails['members'] as $member): ?>
                                        <li><?= htmlspecialchars($member) ?></li>
                                    <?php endforeach; ?>
                                    <?php if (empty($houseDetails['members'])): ?>
                                        <li>No members found</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>&nbsp;&nbsp;You are not yet a member of any house. <a href="house_finder.php">Find House</a></p>
                    <?php endif; ?>
                </div>

                <!-- Preferences Section -->
                <div class="dashboard-section">
                    <h2 class="section-title">Additional House Information</h2>
                    <?php if (!empty($house)): ?>
                        <div class="section-container">
                            <p><strong>Max Occupancy:</strong> <?= isset($house['max_occupancy']) ? htmlspecialchars($house['max_occupancy']) : '' ?></p>
                            <p><strong>Furnishing:</strong> <?= isset($house['furnishing_condition']) ? htmlspecialchars($house['furnishing_condition']) : '' ?></p>
                            <p><strong>WiFi:</strong> <?= isset($house['wifi_status']) ? htmlspecialchars($house['wifi_status']) : '' ?></p>
                            <p><strong>Monthly Rental:</strong> RM <?= isset($house['monthly_rental']) ? htmlspecialchars($house['monthly_rental']) : '' ?></p>
                            <p><strong>Deposit:</strong> RM <?= isset($house['deposit']) ? htmlspecialchars($house['deposit']) : '' ?></p>
                        </div>
                    <?php else: ?>
                        <p><a href="house_finder.php"></a></p>
                    <?php endif; ?>

                    <h2 class="section-title">House Preferences</h2>
                    <?php if ($preferences_exist): ?>
                        <div class="section-container">
                            <p><strong>Gender Preference:</strong> <?= htmlspecialchars($gender_preference) ?></p>
                            <p><strong>Study Year Preference:</strong> <?= htmlspecialchars($study_year_preference) ?></p>
                            <p><strong>Pet Policy:</strong> <?= htmlspecialchars($pet_policy_preference) ?></p>
                        </div>
                    <?php else: ?>
                        <p><a href="#"></a></p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
