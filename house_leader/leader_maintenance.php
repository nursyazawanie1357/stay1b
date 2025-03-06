<?php
session_name("house_leader_session");
session_start();
include '../db_connection.php'; // Database connection file
include 'leader_sidebar.php'; // Sidebar for house leaders

// Check if the house leader is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: leader_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id']; // Leader's housemate ID

// Fetch the leader's house number
$house_number = null;
$stmt = $conn->prepare("
    SELECT lh.house_number 
    FROM house_group hg
    INNER JOIN house_booking hb ON hg.booked_house_id = hb.booked_house_id
    INNER JOIN landlord_house lh ON hb.house_id = lh.house_id
    WHERE hg.housemate_id = ?
");
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$stmt->bind_result($house_number);
$stmt->fetch();
$stmt->close();

$success_message = null;
$error_message = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maintenance_id = isset($_POST['maintenance_id']) ? $_POST['maintenance_id'] : null;
    $description = trim($_POST['description']);
    $photo_path = isset($_POST['existing_photo_path']) ? $_POST['existing_photo_path'] : null;

    if (empty($description)) {
        $error_message = "Description cannot be empty.";
    } else {
        // Handle file upload if a new photo is provided
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "../assets/Maintenance/";
            $file_name = time() . "_" . basename($_FILES['photo']['name']);
            $photo_path = $upload_dir . $file_name;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                $photo_path = null;
                $error_message = "Failed to upload the photo.";
            }
        }

        if (!$error_message) {
            if ($maintenance_id) {
                // Update existing maintenance request
                $stmt = $conn->prepare("UPDATE maintenance_requests SET description = ?, photo_path = ? WHERE maintenance_id = ?");
                $stmt->bind_param("ssi", $description, $photo_path, $maintenance_id);
                if ($stmt->execute()) {
                    $success_message = "Maintenance request updated successfully.";
                } else {
                    $error_message = "Failed to update the maintenance request.";
                }
                $stmt->close();
            } else {
                // Insert new maintenance request
                $stmt = $conn->prepare("
                    INSERT INTO maintenance_requests (house_id, description, photo_path) 
                    VALUES ((SELECT house_id FROM landlord_house WHERE house_number = ?), ?, ?)
                ");
                $stmt->bind_param("sss", $house_number, $description, $photo_path);
                if ($stmt->execute()) {
                    $success_message = "Maintenance request submitted successfully.";
                } else {
                    $error_message = "Failed to submit the maintenance request.";
                }
                $stmt->close();
            }

            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        }
    }
}

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Maintenance request submitted successfully.";
}

// Handle delete action
if (isset($_GET['delete'])) {
    $maintenance_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM maintenance_requests WHERE maintenance_id = ?");
    $stmt->bind_param("i", $maintenance_id);
    if ($stmt->execute()) {
        $success_message = "Maintenance request deleted successfully.";
    } else {
        $error_message = "Failed to delete the maintenance request.";
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all maintenance requests for this house leader
$maintenance_requests = [];
$stmt = $conn->prepare("
    SELECT mr.maintenance_id, lh.house_number, mr.description, mr.photo_path, mr.status, mr.scheduled_date, mr.created_at 
    FROM maintenance_requests mr
    INNER JOIN landlord_house lh ON mr.house_id = lh.house_id
    WHERE lh.house_number = ?
    ORDER BY mr.created_at DESC
");
$stmt->bind_param("s", $house_number);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $maintenance_requests[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Maintenance Requests - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">

    <script>
        function toggleForm(edit = false, maintenanceId = null, description = "", photoPath = "") {
            const form = document.getElementById("maintenance-form");
            form.classList.toggle("hidden");
            if (edit) {
                document.getElementById("maintenance_id").value = maintenanceId;
                document.getElementById("description").value = description;
                document.getElementById("existing_photo_path").value = photoPath;
            } else {
                document.getElementById("maintenance_id").value = "";
                document.getElementById("description").value = "";
                document.getElementById("existing_photo_path").value = "";
            }
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <main>
            <header>
                <h1>Manage Maintenance Requests</h1>
            </header>

            <?php
// Display success or error messages if available
if (isset($_SESSION['success_message'])) {
    echo "<p class='success-message'>" . htmlspecialchars($_SESSION['success_message']) . "</p>";
    unset($_SESSION['success_message']); // Clear the message
}

if (isset($_SESSION['error_message'])) {
    echo "<p class='error-message'>" . htmlspecialchars($_SESSION['error_message']) . "</p>";
    unset($_SESSION['error_message']); // Clear the message
}
?>

            <section class="maintenance-requests">
                <h2>Maintenance Requests</h2>
                <table class="maintenance-table">
                    <thead>
                        <tr>
                            <th>House Number</th>
                            <th>Description</th>
                            <th>Photo</th>
                            <th>Status</th>
                            <th>Scheduled Date</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($maintenance_requests) > 0): ?>
                            <?php foreach ($maintenance_requests as $request): ?>
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
                                    <td><?php echo ucfirst(htmlspecialchars($request['status'])); ?></td>
                                    <td><?php echo $request['scheduled_date'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($request['scheduled_date']))) : 'Not Scheduled'; ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($request['created_at']))); ?></td>
                                    <td>
                                        <a href="javascript:void(0);" class="edit-link" onclick="toggleForm(true, '<?php echo $request['maintenance_id']; ?>', '<?php echo htmlspecialchars($request['description']); ?>', '<?php echo htmlspecialchars($request['photo_path']); ?>')">Edit</a>
                                        <a href="?delete=<?php echo htmlspecialchars($request['maintenance_id']); ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this request?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7"></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <div class="form-toggle">
            <button onclick="toggleForm()" style="width: 100%; display: block;">Add New Maintenance Request</button>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" id="maintenance-form" class="hidden">
                <input type="hidden" id="maintenance_id" name="maintenance_id">
                <input type="hidden" id="existing_photo_path" name="existing_photo_path">
                <label for="house_number">House Number:</label>
                <input type="text" id="house_number" name="house_number" value="<?php echo htmlspecialchars($house_number); ?>" readonly>

                <label for="description">Description of Issue:</label>
                <textarea id="description" name="description" required></textarea>

                <label for="photo">Upload Photo (Optional):</label>
                <input type="file" id="photo" name="photo">

                <button type="submit">Submit Request</button>
            </form>
        </main>
    </div>
</body>
</html>
