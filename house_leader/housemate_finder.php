<?php
session_name("house_leader_session");
session_start();
include '../db_connection.php'; // Database connection file
include 'leader_sidebar.php'; // Sidebar for leaders

// Check if the house leader is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: leader_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id']; // Leader's housemate ID

// Fetch housemate applications for the leader's house group
$query_applications = "
    SELECT 
        hf.application_id,
        hf.booked_house_id,
        hf.housemate_id,
        hf.application_status,
        t.tenant_full_name,
        t.tenant_whatsapp
    FROM housemate_application hf
    INNER JOIN house_booking hb ON hf.booked_house_id = hb.booked_house_id
    INNER JOIN housemate_role hr ON hf.housemate_id = hr.housemate_id
    INNER JOIN tenant t ON hr.tenant_id = t.tenant_id
    WHERE hb.housemate_id = ?
    ORDER BY hf.application_id DESC
";

$stmt = $conn->prepare($query_applications);
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$result_applications = $stmt->get_result();
$applications = $result_applications->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle accept/reject/revert actions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'], $_GET['application_id'])) {
    $application_id = intval($_GET['application_id']);
    $action = $_GET['action'];

    // Fetch additional parameters if needed
    $housemate_id_to_add = isset($_GET['housemate_id']) ? intval($_GET['housemate_id']) : null;
    $booked_house_id = isset($_GET['booked_house_id']) ? intval($_GET['booked_house_id']) : null;

    // Determine the new status based on the action
    switch ($action) {
        case 'accept':
            $new_status = 'approved';

            // Check if housemate already exists in the group
            $check_query = "SELECT COUNT(*) AS count FROM house_group WHERE housemate_id = ? AND booked_house_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $housemate_id_to_add, $booked_house_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row['count'] > 0) {
                $_SESSION['message'] = "This housemate is already in the group.";
                header("Location: housemate_finder.php?error=already_in_group");
                exit();
            }

            // Add the housemate to the group
            $insert_group_query = "INSERT INTO house_group (house_group_id, booked_house_id, housemate_id) VALUES (NULL, ?, ?)";
            $stmt = $conn->prepare($insert_group_query);
            $stmt->bind_param("ii", $booked_house_id, $housemate_id_to_add);

            if (!$stmt->execute()) {
                $_SESSION['message'] = "Error adding housemate to group: " . $stmt->error;
                header("Location: housemate_finder.php?error=insert_failed");
                exit();
            }
            $stmt->close();
            break;

        case 'reject':
            $new_status = 'rejected';
            break;

        default:
            $_SESSION['message'] = "Invalid action.";
            header("Location: housemate_finder.php?error=invalid_action");
            exit();
    }

    // Update the application status
    $update_query = "UPDATE housemate_application SET application_status = ? WHERE application_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $application_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Application status updated successfully.";
        header("Location: housemate_finder.php?success=1");
    } else {
        echo "Error updating application status: " . $stmt->error;
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
    <title>Housemate Finder - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <style>
        .accept-link {
            color: #28a745; /* Green for Accept */
            font-size: 14px;
            text-decoration: none;
            margin-right: 10px;
        }
        .accept-link:hover {
            text-decoration: underline;
        }
        .reject-link {
            color: #cc2000; /* Red for Reject */
            font-size: 14px;
            text-decoration: none;
            margin-right: 10px;
        }
        .reject-link:hover {
            text-decoration: underline;
        }
        .revert-link {
            color: #ffc107; /* Yellow for Revert */
            font-size: 14px;
            text-decoration: none;
        }
        .revert-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <main>
            <header>
                <h1>Housemate Finder</h1>
            </header>
            <section>
                <h2>All House Member Application Requests</h2>
                <?php if (count($applications) > 0): ?>
                    <table class="applications-table">
                        <thead>
                            <tr>
                                <th>Applicant Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php foreach ($applications as $application): ?>
        <tr>
            <td><?= htmlspecialchars($application['tenant_full_name']) ?></td>
            <td>
                <a href="https://wa.me/<?= htmlspecialchars($application['tenant_whatsapp']) ?>" target="_blank">
                    <?= htmlspecialchars($application['tenant_whatsapp']) ?>
                </a>
            </td>
            <td><?= ucfirst(htmlspecialchars($application['application_status'])) ?></td>
            <td>
                <?php if ($application['application_status'] === 'pending'): ?>
                    <a 
                        href="housemate_finder.php?action=accept&application_id=<?= $application['application_id'] ?>&booked_house_id=<?= $application['booked_house_id'] ?>&housemate_id=<?= $application['housemate_id'] ?>" 
                        class="accept-link">Accept</a>
                    <a 
                        href="housemate_finder.php?action=reject&application_id=<?= $application['application_id'] ?>" 
                        class="reject-link">Reject</a>
                <?php else: ?>
                    <!-- Additional handling if needed for non-pending statuses -->
               
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

                    </table>
                <?php else: ?>
                    <p>No house member application requests yet.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
