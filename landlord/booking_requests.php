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

// Fetch all booking requests
$query_requests = "
    SELECT 
        hb.booked_house_id, 
        hb.house_id, 
        hb.housemate_id, 
        hb.booking_status, 
        hb.created_at, 
        lh.house_number, 
        lh.availability_status, 
        t.tenant_full_name, 
        t.tenant_whatsapp 
    FROM house_booking hb
    INNER JOIN landlord_house lh ON hb.house_id = lh.house_id
    INNER JOIN housemate_role hr ON hb.housemate_id = hr.housemate_id
    INNER JOIN tenant t ON hr.tenant_id = t.tenant_id
    WHERE lh.landlord_id = ?
    ORDER BY hb.created_at DESC
";
$stmt = $conn->prepare($query_requests);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result_requests = $stmt->get_result();
$booking_requests = [];
while ($row = $result_requests->fetch_assoc()) {
    $booking_requests[] = $row;
}
$stmt->close();

// Handle accept/reject/revert actions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'], $_GET['booked_house_id'])) {
    $booked_house_id = intval($_GET['booked_house_id']);
    $action = $_GET['action'];

    if ($action === 'accept') {
        $new_status = 'approved';

        // Fetch the house ID from the booking
        $house_id_query = "SELECT house_id FROM house_booking WHERE booked_house_id = ?";
        $stmt_house_id = $conn->prepare($house_id_query);
        $stmt_house_id->bind_param("i", $booked_house_id);
        $stmt_house_id->execute();
        $stmt_house_id->bind_result($house_id);
        $stmt_house_id->fetch();
        $stmt_house_id->close();

        // Check if the house is already marked as Sold Out
        $check_query = "SELECT availability_status FROM landlord_house WHERE house_id = ?";
        $stmt_check = $conn->prepare($check_query);
        $stmt_check->bind_param("i", $house_id);
        $stmt_check->execute();
        $stmt_check->bind_result($availability_status);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($availability_status === 'Sold Out') {
            echo "<script>alert('The house is already marked as Sold Out. Cannot accept another booking.');</script>";
            exit();
        }

        // Insert into house_group when the booking is accepted
        $housemate_id = intval($_GET['housemate_id']); // Assuming housemate_id is passed via GET
        $insert_group_query = "
            INSERT INTO house_group (booked_house_id, housemate_id) 
            VALUES (?, ?)
        ";
        $stmt = $conn->prepare($insert_group_query);
        $stmt->bind_param("ii", $booked_house_id, $housemate_id);

        if ($stmt->execute()) {
            // Update the landlord_house table to mark the house as Sold Out
            $update_house_query = "UPDATE landlord_house SET availability_status = 'Sold Out' WHERE house_id = ?";
            $stmt_update = $conn->prepare($update_house_query);
            $stmt_update->bind_param("i", $house_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
    } else {
        echo "Invalid action.";
        exit();
    }

    $update_query = "UPDATE house_booking SET booking_status = ? WHERE booked_house_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $booked_house_id);

    if ($stmt->execute()) {
        header("Location: booking_requests.php?success=1");
        exit();
    } else {
        echo "Error: " . $conn->error;
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
    <title>Booking Requests - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/landlord.css">
    <style>
        .accept-link {
            color: #28a745;
            font-size: 14px;
            text-decoration: none;
            margin-right: 10px;
        }

        .accept-link:hover {
            color: #28a745;
            text-decoration: underline;
        }

        .reject-link {
            color: #cc2000;
            font-size: 14px;
            text-decoration: none;
            margin-right: 10px;
        }

        .reject-link:hover {
            color: #cc2000;
            text-decoration: underline;
        }

        .revert-link {
            color: #ffc107;
            font-size: 14px;
            text-decoration: none;
        }

        .revert-link:hover {
            color: #ffc107;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">

        <!-- Main Content -->
        <main>
            <header>
                <h1>Booking Requests</h1>
            </header>

            <section>
                <h2>All Booking Requests</h2>
                <?php if (count($booking_requests) > 0): ?>
                    <table class="booking-requests-table">
                        <thead>
                            <tr>
                                <th>House Number</th>
                                <th>Tenant Name</th>
                                <th>Contact</th>
                                <th>Requested Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($booking_requests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['house_number']) ?></td>
                                    <td><?= htmlspecialchars($request['tenant_full_name']) ?></td>
                                    <td>
                                        <a href="https://wa.me/<?= htmlspecialchars($request['tenant_whatsapp']) ?>" target="_blank">
                                            <?= htmlspecialchars($request['tenant_whatsapp']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($request['created_at']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($request['booking_status'])) ?></td>
                                    <td>
                                    <?php if ($request['booking_status'] === 'pending'): ?>
    <a 
        href="booking_requests.php?action=accept&booked_house_id=<?= $request['booked_house_id'] ?>&housemate_id=<?= $request['housemate_id'] ?>" 
        class="accept-link">Accept</a>
    <a 
        href="booking_requests.php?action=reject&booked_house_id=<?= $request['booked_house_id'] ?>" 
        class="reject-link">Reject</a>
<?php else: ?>

<?php endif; ?>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No booking requests available.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
