<?php
// includes/header.php
?>
<div class="top-bar d-flex justify-content-between align-items-center mb-4">
    <h2 id="section-title">Teacher Dashboard</h2>
    <div class="icons">
        <div class="dropdown mr-3">
            <a href="#" class="dropdown-toggle" id="notificationIcon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell"></i>
                <span id="notification-count" class="badge badge-danger" style="display: none;">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notificationIcon" id="notification-dropdown">
                <p class="dropdown-header">Notifications</p>
                <div id="notification-items">No new notifications.</div>
            </div>
        </div>
        <div class="dropdown">
            <a href="#" class="dropdown-toggle" id="profileIcon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-user-circle"></i>
                <span>Teacher</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="profileIcon">
                <a class="dropdown-item" href="/AcadMeter/server/controllers/logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>
