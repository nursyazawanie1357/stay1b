<?php
// Ensure this file is being included after session_start() and DB connection are established in the main files.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$housemate_id = $_SESSION['housemate_id'];

// Fetch leader details
$query_leader = "SELECT tenant.tenant_full_name, tenant.tenant_picture
                 FROM housemate_role
                 JOIN tenant ON housemate_role.tenant_id = tenant.tenant_id
                 WHERE housemate_role.housemate_id = ? AND housemate_role.house_role = 'leader'";
$stmt = $conn->prepare($query_leader);
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$stmt->bind_result($leader_username, $leader_picture);
$stmt->fetch();
$stmt->close();

if (empty($leader_picture)) {
    $leader_picture = "assets/profile-pictures/default-profile.png";
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
        <p>House Leader</p>
    </div>
    <div class="profile-section">
        <img src="../<?= htmlspecialchars($leader_picture) ?>" alt="Profile Picture" class="profile-picture">
        <h2>Welcome, <?= htmlspecialchars($leader_username) ?></h2>
        <a href="leader_profile.php">Edit Profile</a>
    </div>
    <ul class="nav-links">
        <li><a href="leader_dashboard.php" class="<?= $current_page == 'leader_dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="house_listings.php" class="<?= $current_page == 'house_listings.php' ? 'active' : '' ?>">House Listings</a></li>
        <li><a href="housemate_finder.php" class="<?= $current_page == 'housemate_finder.php' ? 'active' : '' ?>">Housemate Finder</a></li>
        <li><a href="leader_agreement.php" class="<?= $current_page == 'leader_agreement.php' ? 'active' : '' ?>">Tenancy Agreement</a></li>
        <li><a href="leader_expenses.php" class="<?= $current_page == 'leader_expenses.php' ? 'active' : '' ?>">House Expenses</a></li>
        <li><a href="leader_house_review.php" class="<?= $current_page == 'leader_house_review.php' ? 'active' : '' ?>">House Reviews</a></li>
        <li><a href="leader_maintenance.php" class="<?= $current_page == 'leader_maintenance.php' ? 'active' : '' ?>">Maintenance Request</a></li>
        <li><a href="leader_notices.php" class="<?= $current_page == 'leader_notices.php' ? 'active' : '' ?>">Important Notices</a></li>
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
