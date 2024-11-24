<?php
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: /AcadMeter/public/login.php');
    exit;
}

// Include database connection using __DIR__ for absolute path
require_once __DIR__ . '/../config/db_connection.php';

// Rest of your PHP code...

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AcadMeter</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/admin_dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="./assets/img/acadmeter_logo.png" alt="AcadMeter Logo" class="logo">
                <h3>AcadMeter</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-link active" data-section="dashboard-overview">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-link" data-section="user-management">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Users</span>
                </a>
                <a href="#" class="nav-link" data-section="activity-logs">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
                <a href="#" class="nav-link" data-section="reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="#" class="nav-link" data-section="settings">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar" style="position: relative; z-index: 1000;">
                <button id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h2 id="section-title">Dashboard</h2>
                <div class="user-actions">
                    <div class="dropdown" id="notificationDropdown">
                        <button class="dropdown-toggle" id="notificationIcon" aria-label="Notifications">
                            <i class="fas fa-bell"></i>
                            <span id="notification-count" class="badge" aria-label="Notification Count">0</span>
                        </button>
                        <div class="dropdown-menu notification-menu" id="notification-dropdown">
                            <h3 class="dropdown-header">Notifications</h3>
                            <div id="notification-items" class="notification-items">
                                <!-- Notifications will be dynamically loaded here -->
                            </div>
                        </div>
                    </div>
                    <div class="dropdown" id="userDropdown">
                        <button class="dropdown-toggle" id="profileIcon" aria-label="User Menu">
                            <img src="https://ui-avatars.com/api/?name=Admin&background=0D8ABC&color=fff" alt="User Avatar" class="user-avatar">
                            <span>Admin</span>
                        </button>
                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/AcadMeter/server/controllers/logout.php" class="dropdown-item" id="logoutButton">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Overview Section -->
            <section id="dashboard-overview" class="content-section">
                <div class="card-grid">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <i class="fas fa-users card-icon"></i>
                            <h5 class="card-title">Total Users</h5>
                            <p class="card-text" id="total-users">Loading...</p>
                        </div>
                    </div>
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <i class="fas fa-user-clock card-icon"></i>
                            <h5 class="card-title">Pending Approvals</h5>
                            <p class="card-text" id="pending-approvals-count">Loading...</p>
                        </div>
                    </div>
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <i class="fas fa-history card-icon"></i>
                            <h5 class="card-title">Audit Logs</h5>
                            <p class="card-text" id="audit-logs">Loading...</p>
                        </div>
                    </div>
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <i class="fas fa-file-alt card-icon"></i>
                            <h5 class="card-title">Reports Generated</h5>
                            <p class="card-text" id="reports-generated">Loading...</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- User Management Section -->
            <section id="user-management" class="content-section" style="display: none;">
                <div class="card">
                    <div class="card-body">
                        <div class="tab-buttons">
                            <button class="tab-btn active" id="pending-approvals-btn" onclick="showTab('pending-approvals')">
                                <i class="fas fa-hourglass-half"></i> Pending Approvals
                            </button>
                            <button class="tab-btn" id="delete-users-btn" onclick="showTab('delete-users')">
                                <i class="fas fa-user-slash"></i> Delete Users
                            </button>
                        </div>

                        <div class="tab-content">
                            <div id="pending-approvals" class="tab-pane">
                                <div id="pending-approvals-content">
                                    <!-- Table or message will be injected here -->
                                </div>
                            </div>

                            <div id="delete-users" class="tab-pane" style="display: none;">
                                <div id="delete-users-content">
                                    <!-- Table or message will be injected here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Activity Logs Section -->
            <section id="activity-logs" class="content-section" style="display: none;">
                <div class="card">
                    <div class="card-body">
                        <h3 class="section-title"><i class="fas fa-history"></i> Activity Logs</h3>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Sample data; replace with dynamic data as needed -->
                                    <tr>
                                        <td>2023-05-15 14:30:22</td>
                                        <td>John Doe</td>
                                        <td>User Login</td>
                                        <td>Successful login from IP: 192.168.1.100</td>
                                    </tr>
                                    <tr>
                                        <td>2023-05-15 15:45:10</td>
                                        <td>Jane Smith</td>
                                        <td>Grade Update</td>
                                        <td>Updated grades for Math 101</td>
                                    </tr>
                                    <tr>
                                        <td>2023-05-15 16:20:05</td>
                                        <td>Admin</td>
                                        <td>User Approval</td>
                                        <td>Approved new user registration: Mark Johnson</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reports Section -->
            <section id="reports" class="content-section" style="display: none;">
                <div class="card">
                    <div class="card-body">
                        <h3 class="section-title"><i class="fas fa-chart-bar"></i> Reports</h3>
                        <div class="tab-buttons">                           
                            <button class="tab-btn" onclick="showReportTab('user-activity')">User Activity</button>
                        </div>
                        <div class="tab-content">
                            <div id="grade-distribution" class="tab-pane">
                                <h4></h4>
                                <p>User activity last 30 days</p>
                            </div>
                            <div id="user-activity" class="tab-pane" style="display: none;">
                                <h4>User Activity Report</h4>
                                <p>Placeholder for user activity chart</p>
                            </div>
                            <div id="system-performance" class="tab-pane" style="display: none;">
                                <h4>System Performance Report</h4>
                                <p>Placeholder for system performance metrics</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Alert Placeholder -->
    <div id="alertPlaceholder" class="alert-container"></div>

    <!-- Include Admin Dashboard JS -->
    <script src="./assets/js/admin_dashboard.js"></script>
</body>
</html>
