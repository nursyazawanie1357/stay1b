<?php
session_name("house_leader_session");
session_start();
include '../db_connection.php'; // Adjust the path if necessary
include 'leader_sidebar.php'; // Sidebar for leaders

// Check if the leader is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: leader_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id'];

// Fetch the house details for the logged-in leader
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
$house = $result->fetch_assoc() ?: []; // Default to empty array if no house data is found
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
    'leader' => '',
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

// Handle form submission for preferences
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gender_preference = $_POST['genderPreference'];
    $study_year_preference = $_POST['yearPreference'];
    $pet_policy_preference = $_POST['petPolicy'];

    // Fetch the booked_house_id from house_group
    $fetch_house_id_query = "SELECT booked_house_id FROM house_group WHERE housemate_id = ?";
    $stmt = $conn->prepare($fetch_house_id_query);
    $stmt->bind_param("i", $housemate_id);
    $stmt->execute();
    $stmt->bind_result($booked_house_id);
    $stmt->fetch();
    $stmt->close();

    if ($booked_house_id) {
        // Delete existing preferences
        $delete_query = "DELETE FROM house_preferences WHERE booked_house_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $booked_house_id);
        $stmt->execute();
        $stmt->close();

        // Insert new preferences
        $insert_query = "INSERT INTO house_preferences (booked_house_id, gender_preference, study_year_preference, pet_policy_preference) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isss", $booked_house_id, $gender_preference, $study_year_preference, $pet_policy_preference);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "No booked house found for this housemate.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leader Dashboard - Stay1B</title>
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
        form {
        width: 90%;
        max-width: 400px;
        max-height: auto;
        background: white;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #C5C6C7;
}

    </style>
</head>
<body>
    <div class="member-dashboard-wrapper">
        <!-- Main Content -->
        <main>
            <header>
                <h1>Leader Dashboard</h1>
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
                        <p>&nbsp;&nbsp;No house yet. <a href="house_listings.php">Find House</a>.</p>
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
                        <p>No house details available.</p>
                    <?php endif; ?>

                    <h2 class="section-title">House Preferences</h2>
                    <div class="section-container">
                        <form method="POST">
                            <label for="genderPreference">Gender Preference:</label>
                            <select id="genderPreference" name="genderPreference">
                                <option value="Any" <?= $gender_preference == 'Any' ? 'selected' : '' ?>>Any</option>
                                <option value="Male" <?= $gender_preference == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $gender_preference == 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                            <label for="yearPreference">Year of Study:</label>
                            <select id="yearPreference" name="yearPreference">
                                <option value="Any" <?= $study_year_preference == 'Any' ? 'selected' : '' ?>>Any</option>
                                <option value="1st Year" <?= $study_year_preference == '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2nd Year" <?= $study_year_preference == '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3rd Year" <?= $study_year_preference == '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4th Year" <?= $study_year_preference == '4th Year' ? 'selected' : '' ?>>4th Year</option>
                            </select>
                            <label for="petPolicy">Pet Policy:</label>
                            <select id="petPolicy" name="petPolicy">
                                <option value="Pets Allowed" <?= $pet_policy_preference == 'Pets Allowed' ? 'selected' : '' ?>>Pets Allowed</option>
                                <option value="No Pets" <?= $pet_policy_preference == 'No Pets' ? 'selected' : '' ?>>No Pets</option>
                            </select>
                            <button type="submit">Save Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
