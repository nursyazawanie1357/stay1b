<?php
include '../db_connection.php';

// Start the session only if it hasn’t already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the landlord is logged in
if (!isset($_SESSION['landlord_id'])) {
    header("Location: landlord_login.php");
    exit();
}

$landlord_id = $_SESSION['landlord_id'];

// Fetch landlord details
$query = "SELECT landlord_full_name, landlord_picture FROM landlord WHERE landlord_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $landlord_id);
    $stmt->execute();
    $stmt->bind_result($landlord_full_name, $landlord_picture);
    $stmt->fetch();
    $stmt->close();
}

// Use default profile picture if none is uploaded
if (empty($landlord_picture)) {
    $landlord_picture = "assets/profile-pictures/default-profile.png";
}

// Get the current page to highlight the active link
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
        <p>Landlord</p>
    </div>
    <div class="profile-section">
        <img src="../<?= htmlspecialchars($landlord_picture) ?>" alt="Profile Picture" class="profile-picture">
        <h2>Welcome, <?= htmlspecialchars($landlord_full_name) ?></h2>
        <a href="landlord_profile.php">Edit Profile</a>
    </div>
    <ul class="nav-links">
        <li><a href="landlord_dashboard.php" class="<?= ($current_page == 'landlord_dashboard.php') ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="create_listing.php" class="<?= ($current_page == 'create_listing.php') ? 'active' : '' ?>">Create Listing</a></li>
        <li><a href="booking_requests.php" class="<?= ($current_page == 'booking_requests.php') ? 'active' : '' ?>">Booking Requests</a></li>
        <li><a href="agreement.php" class="<?= ($current_page == 'agreement.php') ? 'active' : '' ?>">Tenancy Agreement</a></li>
        <li><a href="landlord_expenses.php" class="<?= ($current_page == 'landlord_expenses.php') ? 'active' : '' ?>">Manage Expenses</a></li>
        <li><a href="landlord_notices.php" class="<?= ($current_page == 'landlord_notices.php') ? 'active' : '' ?>">Important Notices</a></li>
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
