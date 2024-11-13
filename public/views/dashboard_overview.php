<?php
// public/views/dashboard_overview.php
?>
<div id="dashboard-overview" class="content-section">
    <h2>Dashboard Overview <i class="fas fa-info-circle" data-toggle="tooltip" title="Overview of key metrics and notifications."></i></h2>

    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Students</h5>
                    <p class="card-text" id="total-students">Loading...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-secondary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Average Grades</h5>
                    <p class="card-text" id="average-grades">Loading...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">At-Risk Students</h5>
                    <p class="card-text" id="at-risk-students">Loading...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Attendance Issues</h5>
                    <p class="card-text" id="attendance-issues">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header">
            Notifications
        </div>
        <div class="card-body">
            <ul id="notification-list">
                <li>No notifications at this time.</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('total-students').textContent = "150";  // Example static data; replace with dynamic fetch
    document.getElementById('average-grades').textContent = "82%";
    document.getElementById('at-risk-students').textContent = "5";
    document.getElementById('attendance-issues').textContent = "2";
});
</script>
