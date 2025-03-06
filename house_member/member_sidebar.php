<?php
// Include the database connection file (Ensure this is included only once)
include '../db_connection.php'; // Adjust the path if necessary

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$housemate_id = $_SESSION['housemate_id']; // Get the housemate ID from the session

// Fetch member details
$query_member = "SELECT tenant.tenant_full_name, tenant.tenant_picture
                 FROM housemate_role
                 JOIN tenant ON housemate_role.tenant_id = tenant.tenant_id
                 WHERE housemate_role.housemate_id = ? AND housemate_role.house_role = 'member'";
$stmt = $conn->prepare($query_member);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error); // Handle error if statement preparation fails
}

$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$stmt->bind_result($member_username, $member_picture);
$stmt->fetch();
$stmt->close();

// If no profile picture is set, use the default one
if (empty($member_picture)) {
    $member_picture = "assets/profile-pictures/default-profile.png";
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Hamburger Menu -->
<button class="hamburger" id="hamburger">
    &#9776; <!-- Hamburger icon -->
</button>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="branding">
        <h1>SAMS</h1>
        <p>House Member</p>
    </div>
    <div class="profile-section">
        <img src="../<?= htmlspecialchars($member_picture) ?>" alt="Profile Picture" class="profile-picture">
        <h2>Welcome, <?= htmlspecialchars($member_username) ?></h2>
        <a href="member_profile.php">Edit Profile</a>
    </div>
    <ul class="nav-links">
        <li><a href="member_dashboard.php" class="<?= $current_page == 'member_dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="house_finder.php" class="<?= $current_page == 'house_finder.php' ? 'active' : '' ?>">House Finder</a></li>
        <li><a href="member_agreement.php" class="<?= $current_page == 'member_agreement.php' ? 'active' : '' ?>">Tenancy Agreement</a></li>
        <li><a href="member_expenses.php" class="<?= $current_page == 'member_expenses.php' ? 'active' : '' ?>">House Expenses</a></li>
        <li><a href="member_house_review.php" class="<?= $current_page == 'member_house_review.php' ? 'active' : '' ?>">House Reviews</a></li>
        <li><a href="member_notices.php" class="<?= $current_page == 'member_notices.php' ? 'active' : '' ?>">Important Notices</a></li>
    </ul>
    <a href="../index.php" class="logout-link">Log Out</a>
</nav>

<!-- JavaScript for Sidebar Toggle -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');

        hamburger.addEventListener('click', function () {
            sidebar.classList.toggle('open'); // Toggle sidebar visibility
        });
    });
</script>
