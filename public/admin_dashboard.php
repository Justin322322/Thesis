<?php
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Head content: meta tags, title, links to CSS -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AcadMeter</title>
    <!-- Include Bootstrap CSS and other stylesheets -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
</head>
<body>
    <div class="sidebar">
        <img src="assets/img/acadmeter_logo.png" alt="AcadMeter Logo">
        <h3>AcadMeter Admin</h3>
        <a href="#" class="nav-link active" data-section="dashboard-overview"><i class="fas fa-tachometer-alt"></i> Dashboard Overview</a>
        <a href="#" class="nav-link" data-section="user-management"><i class="fas fa-users"></i> User Management</a>
        <a href="#" class="nav-link" data-section="activity-logs"><i class="fas fa-file-alt"></i> Activity Logs</a>
        <a href="#" class="nav-link" data-section="reports"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="#" class="nav-link" data-section="settings"><i class="fas fa-cog"></i> Settings</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2 id="section-title">Dashboard Overview - Admin Dashboard</h2>
            <div class="icons">
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle" id="notificationIcon" data-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span id="notification-count" class="badge badge-danger" style="display: none;">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" id="notification-dropdown">
                        <p class="dropdown-header">Notifications</p>
                        <div id="notification-items">
                            <!-- Notifications will be dynamically loaded here -->
                        </div>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle" id="profileIcon" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        <span>Admin</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="/AcadMeter/server/controllers/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div id="dashboard-overview" class="content-section">
            <div class="card-deck mt-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Users</h5>
                        <p class="card-text" id="total-users">Loading...</p>
                    </div>
                </div>
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5>Pending Approvals</h5>
                        <p class="card-text" id="pending-approvals-count">Loading...</p>
                    </div>
                </div>
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Audit Logs</h5>
                        <p class="card-text" id="audit-logs">Loading...</p>
                    </div>
                </div>
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Reports Generated</h5>
                        <p class="card-text" id="reports-generated">Loading...</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="user-management" class="content-section" style="display: none;">
            <div class="card mt-4">
                <div class="card-header">User Management</div>
                <div class="card-body">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <button class="btn btn-outline-primary" id="pending-approvals-btn" onclick="showTab('pending-approvals')">
                            <i class="fas fa-hourglass-half"></i> Pending Approvals
                        </button>
                        <button class="btn btn-outline-primary" id="delete-users-btn" onclick="showTab('delete-users')">
                            <i class="fas fa-user-slash"></i> Delete Users
                        </button>
                    </div>

                    <div class="table-responsive mt-4">
                        <!-- Pending Approvals Content -->
                        <div id="pending-approvals" class="tab-pane">
                            <div id="pending-approvals-content">
                                <!-- Table or message will be injected here -->
                            </div>
                        </div>

                        <!-- Delete Users Content -->
                        <div id="delete-users" class="tab-pane" style="display: none;">
                            <div id="delete-users-content">
                                <!-- Table or message will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Include necessary scripts -->
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <!-- Popper.js for Bootstrap tooltips and popovers -->
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
        <!-- Bootstrap JS -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

        <!-- Custom Script -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const navLinks = document.querySelectorAll('.nav-link');
                const contentSections = document.querySelectorAll('.content-section');
                const sectionTitle = document.getElementById('section-title');
                const tabButtons = document.querySelectorAll('.btn-group .btn');

                navLinks.forEach(link => {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        navLinks.forEach(link => link.classList.remove('active'));
                        this.classList.add('active');
                        contentSections.forEach(section => section.style.display = 'none');
                        const targetSection = document.getElementById(this.dataset.section);
                        targetSection.style.display = 'block';
                        sectionTitle.textContent = `${this.textContent.trim()} - Admin Dashboard`;

                        if (this.dataset.section === 'user-management') {
                            showTab('pending-approvals');
                        }
                    });
                });

                // Make showTab function globally accessible
                window.showTab = function (tabName) {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    document.getElementById('pending-approvals').style.display = 'none';
                    document.getElementById('delete-users').style.display = 'none';

                    if (tabName === 'pending-approvals') {
                        document.getElementById('pending-approvals-btn').classList.add('active');
                        document.getElementById('pending-approvals').style.display = 'block';
                        loadPendingUsers();
                    } else if (tabName === 'delete-users') {
                        document.getElementById('delete-users-btn').classList.add('active');
                        document.getElementById('delete-users').style.display = 'block';
                        loadDeleteUsers();
                    }
                };

                // Load dashboard stats
                function loadDashboardStats() {
                    fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=overview')
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('total-users').textContent = data.total_users || '0';
                            document.getElementById('pending-approvals-count').textContent = data.pending_approvals || '0';
                            document.getElementById('audit-logs').textContent = data.audit_logs || '0';
                            document.getElementById('reports-generated').textContent = data.reports_generated || '0';
                        })
                        .catch(error => console.error('Error fetching dashboard stats:', error));
                }

                // Load pending users
                function loadPendingUsers() {
                    fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=pending_users')
                        .then(response => response.json())
                        .then(data => {
                            const container = document.getElementById('pending-approvals-content');
                            container.innerHTML = '';
                            if (data && data.length > 0) {
                                let tableHTML = `
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Role</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                                data.forEach(user => {
                                    tableHTML += `
                                        <tr>
                                            <td>${user.first_name} ${user.last_name}</td>
                                            <td>${user.user_type}</td>
                                            <td>${user.email}</td>
                                            <td>${user.status}</td>
                                            <td>
                                                <button class="btn btn-success btn-sm" onclick="confirmAction(${user.user_id}, 'approve')">Approve</button>
                                                <button class="btn btn-danger btn-sm" onclick="confirmAction(${user.user_id}, 'reject')">Reject</button>
                                            </td>
                                        </tr>`;
                                });
                                tableHTML += '</tbody></table>';
                                container.innerHTML = tableHTML;
                            } else {
                                container.innerHTML = '<p>No pending users.</p>';
                            }
                            // Update notifications
                            loadNotifications();
                        })
                        .catch(error => console.error('Error loading pending users:', error));
                }

                // Load delete users
                function loadDeleteUsers() {
                    fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=delete_users_list')
                        .then(response => response.json())
                        .then(data => {
                            const container = document.getElementById('delete-users-content');
                            container.innerHTML = '';
                            if (data && data.length > 0) {
                                let tableHTML = `
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Role</th>
                                                <th>Email</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                                data.forEach(user => {
                                    tableHTML += `
                                        <tr>
                                            <td>${user.first_name} ${user.last_name}</td>
                                            <td>${user.user_type}</td>
                                            <td>${user.email}</td>
                                            <td>
                                                <button class="btn btn-warning btn-sm" onclick="confirmDelete(${user.user_id})">Delete</button>
                                            </td>
                                        </tr>`;
                                });
                                tableHTML += '</tbody></table>';
                                container.innerHTML = tableHTML;
                            } else {
                                container.innerHTML = '<p>No users available for deletion.</p>';
                            }
                        })
                        .catch(error => console.error('Error loading delete users list:', error));
                }

                window.confirmAction = function (userId, userAction) {
                    const confirmation = confirm(`Are you sure you want to ${userAction} this user?`);
                    if (!confirmation) return;

                    fetch(`/AcadMeter/server/controllers/admin_dashboard_function.php?action=update_user_status`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: userId, userAction: userAction })
                    })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            loadPendingUsers();
                            // Update notifications
                            loadNotifications();
                        })
                        .catch(error => console.error('Error updating user status:', error));
                };

                window.confirmDelete = function (userId) {
                    const confirmation = confirm("Are you sure you want to delete this user?");
                    if (!confirmation) return;

                    fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=delete_user', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: userId })
                    })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            loadDeleteUsers();
                        })
                        .catch(error => console.error('Error deleting user:', error));
                };

                // Load notifications
                function loadNotifications() {
                    fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=get_notifications')
                        .then(response => response.json())
                        .then(data => {
                            const notificationCount = document.getElementById('notification-count');
                            const notificationItems = document.getElementById('notification-items');
                            notificationItems.innerHTML = '';

                            if (data && data.length > 0) {
                                notificationCount.style.display = 'inline-block';
                                notificationCount.textContent = data.length;

                                data.forEach(notification => {
                                    notificationItems.innerHTML += `
                                        <div class="notification-item">
                                            <p>${notification.message}</p>
                                            <small>${notification.timestamp}</small>
                                        </div>`;
                                });
                            } else {
                                notificationCount.style.display = 'none';
                                notificationItems.innerHTML = '<p>No new notifications.</p>';
                            }
                        })
                        .catch(error => console.error('Error loading notifications:', error));
                }

                // Initial load
                loadDashboardStats();
                showTab('pending-approvals');
                loadNotifications();
            });
        </script>
    </div>
</body>
</html>
