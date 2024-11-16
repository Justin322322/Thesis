<div id="dashboard-overview" class="content-section">
    <h2>Dashboard Overview <i class="fas fa-info-circle" data-toggle="tooltip" title="Overview of key metrics and notifications."></i></h2>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Students</h5>
                    <p class="card-text display-4" id="total-students">Loading...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Average Grades</h5>
                    <p class="card-text display-4" id="average-grades">Loading...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">At-Risk Students</h5>
                    <p class="card-text display-4" id="at-risk-students">Loading...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Attendance Issues</h5>
                    <p class="card-text display-4" id="attendance-issues">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Recent Notifications</h5>
        </div>
        <div class="card-body">
            <ul id="notification-list" class="list-group list-group-flush">
                <li class="list-group-item">No notifications at this time.</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simulated data loading
    setTimeout(() => {
        document.getElementById('total-students').textContent = "150";
        document.getElementById('average-grades').textContent = "82%";
        document.getElementById('at-risk-students').textContent = "5";
        document.getElementById('attendance-issues').textContent = "2";
        
        // Update notifications
        const notificationList = document.getElementById('notification-list');
        notificationList.innerHTML = `
            <li class="list-group-item">
                <i class="fas fa-exclamation-circle text-warning mr-2"></i>
                3 students have not submitted their latest assignment.
            </li>
            <li class="list-group-item">
                <i class="fas fa-calendar-check text-success mr-2"></i>
                Parent-teacher conference scheduled for next week.
            </li>
        `;
    }, 1000);
});
</script>