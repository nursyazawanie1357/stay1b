<?php
session_name("admin_session");
session_start();
include '../db_connection.php';
include 'admin_sidebar.php'; 

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch consolidated house details
$query = "
    SELECT 
        lh.house_number,
        hr.house_role,
        t.tenant_full_name,
        ta.start_date AS agreement_start,
        DATE_ADD(ta.start_date, INTERVAL CAST(SUBSTRING_INDEX(ta.tenancy_period, ' ', 1) AS UNSIGNED) YEAR) AS agreement_end
    FROM house_group hg
    JOIN house_booking hb ON hg.booked_house_id = hb.booked_house_id
    JOIN landlord_house lh ON hb.house_id = lh.house_id
    JOIN housemate_role hr ON hg.housemate_id = hr.housemate_id
    JOIN tenant t ON hr.tenant_id = t.tenant_id
    LEFT JOIN tenancy_agreements ta ON hb.booked_house_id = ta.booked_house_id
    ORDER BY lh.house_number, hr.house_role DESC, t.tenant_full_name";

$stmt = $conn->prepare($query);

$houses = [];
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $houseNumber = $row['house_number'];

        if (!isset($houses[$houseNumber])) {
            $houses[$houseNumber] = [
                'agreement_start' => $row['agreement_start'],
                'agreement_end' => $row['agreement_end'],
                'leader' => null,
                'members' => []
            ];
        }

        if ($row['house_role'] === 'leader') {
            $houses[$houseNumber]['leader'] = $row['tenant_full_name'];
        } elseif ($row['house_role'] === 'member') {
            $houses[$houseNumber]['members'][] = $row['tenant_full_name'];
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Reports - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="dashboard-container">
        <main>
            <header>
                <h1>House Reports</h1>
            </header>

            <div class="admin-house-report-container">
                <?php if (!empty($houses)): ?>
                    <?php foreach ($houses as $houseNumber => $details): ?>
                        <div class="admin-house-report-card">
                            <h3 class="admin-house-report-title">House Number: <?= htmlspecialchars($houseNumber) ?></h3>
                            <div class="admin-house-report-details">
                                <p class="admin-house-report-detail">
                                    <strong>Agreement Period:</strong> 
                                    <?= htmlspecialchars($details['agreement_start'] ?? '-') ?> to 
                                    <?= htmlspecialchars($details['agreement_end'] ?? '-') ?>
                                </p>
                                <p class="admin-house-report-detail">
                                    <strong>House Leader:</strong> 
                                    <?= htmlspecialchars($details['leader'] ?? '') ?>
                                </p>
                                <p class="admin-house-report-detail"><strong>House Members:</strong></p>
                                <ul class="admin-house-report-members-list">
                                    <?php foreach ($details['members'] as $member): ?>
                                        <li><?= htmlspecialchars($member) ?></li>
                                    <?php endforeach; ?>
                                    <?php if (empty($details['members'])): ?>
                                        <li>No members</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="admin-no-house-report">No houses found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
