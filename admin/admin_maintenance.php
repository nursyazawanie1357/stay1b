<?php
session_name("admin_session");
session_start();
include '../db_connection.php'; // Database connection file
include 'admin_sidebar.php'; // Sidebar for admins

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

$success_message = null;
$error_message = null;

// Handle update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance_id'], $_POST['status'], $_POST['scheduled_date'])) {
    $maintenance_id = $_POST['maintenance_id'];
    $status = $_POST['status'];
    $scheduled_date = $_POST['scheduled_date'];

    $stmt = $conn->prepare("UPDATE maintenance_requests SET status = ?, scheduled_date = ? WHERE maintenance_id = ?");
    $stmt->bind_param("ssi", $status, $scheduled_date, $maintenance_id);

    if ($stmt->execute()) {
        $success_message = "Maintenance request updated successfully.";
    } else {
        $error_message = "Failed to update the maintenance request.";
    }

    $stmt->close();

    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
}

// Check for success message after redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Maintenance request updated successfully.";
}

// Fetch maintenance requests with house details
$requests = [];
$result = $conn->query("
    SELECT mr.maintenance_id, lh.house_number, mr.description, mr.photo_path, mr.status, mr.scheduled_date
    FROM maintenance_requests mr
    INNER JOIN landlord_house lh ON mr.house_id = lh.house_id
    ORDER BY mr.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Maintenance Requests - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
</head>
<body>
    <div class="dashboard-container">
        <main>
            <header>
                <h1>Manage Maintenance Requests</h1>
            </header>

            <div class="maintenance-requests-section">
                <?php if ($success_message): ?>
                    <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>

                <div class="table-container">
                    <table class="maintenance-requests-table">
                        <thead>
                            <tr>
                                <th>House Number</th>
                                <th>Description</th>
                                <th>Photo</th>
                                <th>Status</th>
                                <th>Scheduled Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['house_number']); ?></td>
                                    <td><?php echo htmlspecialchars($request['description']); ?></td>
                                    <td>
                                        <?php if ($request['photo_path']): ?>
                                            <a href="<?php echo htmlspecialchars($request['photo_path']); ?>" target="_blank">View Photo</a>
                                        <?php else: ?>
                                            No Photo
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(ucfirst($request['status'])); ?></td>
                                    <td><?php echo $request['scheduled_date'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($request['scheduled_date']))) : 'Not Scheduled'; ?></td>
                                    <td>
                                        <form action="" method="POST">
                                            <input type="hidden" name="maintenance_id" value="<?php echo htmlspecialchars($request['maintenance_id']); ?>">
                                            <select name="status" required>
                                                <option value="pending" <?php if ($request['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                                                <option value="accepted" <?php if ($request['status'] === 'accepted') echo 'selected'; ?>>Accept</option>
                                                <option value="rejected" <?php if ($request['status'] === 'rejected') echo 'selected'; ?>>Reject</option>
                                            </select>
                                            <input type="datetime-local" name="scheduled_date" value="<?php echo $request['scheduled_date'] ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($request['scheduled_date']))) : ''; ?>">
                                            <button type="submit">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
