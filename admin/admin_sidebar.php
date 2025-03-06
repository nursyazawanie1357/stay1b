<?php
// Include the database connection file (Ensure this is included only once)
include '../db_connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$admin_id = $_SESSION['admin_id']; // Get the admin ID from the session

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
        <p>Admin Panel</p>
    </div>
    <ul class="nav-links">
        <li><a href="admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="admin_reports.php" class="<?= $current_page == 'admin_reports.php' ? 'active' : '' ?>">House Reports</a></li>
        <li><a href="admin_notices.php" class="<?= $current_page == 'admin_notices.php' ? 'active' : '' ?>">Important Notices</a></li>
        <li><a href="admin_maintenance.php" class="<?= $current_page == 'admin_maintenance.php' ? 'active' : '' ?>">Maintenance</a></li>
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
