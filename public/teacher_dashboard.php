<?php
// File: C:\xampp\htdocs\AcadMeter\public\teacher_dashboard.php

// Include the centralized initialization file
require_once '../config/init.php';

// Redirect to login if not authenticated as Instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.php');
    exit;
}

include('../config/db_connection.php');

// Function to fetch data securely
function fetchData($conn, $query, $param = null, $type = 'i') {
    $stmt = $conn->prepare($query);
    if ($param !== null) {
        $stmt->bind_param($type, $param);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    $stmt->close();
    return $data;
}

$user_id = $_SESSION['user_id'];
$instructorData = fetchData($conn, "SELECT instructor_id FROM instructors WHERE user_id = ?", $user_id);
$instructor_id = isset($instructorData[0]['instructor_id']) ? $instructorData[0]['instructor_id'] : 0;

// Fetch sections and students
$sections = fetchData($conn, "SELECT section_id, section_name FROM sections WHERE instructor_id = ?", $instructor_id);
$students = fetchData($conn, "SELECT s.student_id, u.first_name, u.last_name FROM students s JOIN users u ON s.user_id = u.user_id");

// Set default view to `dashboard_overview`
$allowed_views = ['dashboard_overview', 'class_management', 'grade_management', 'performance_monitoring', 'predictive_analytics', 'feedback', 'reporting'];
$view = isset($_GET['view']) && in_array($_GET['view'], $allowed_views) ? $_GET['view'] : 'dashboard_overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard - AcadMeter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Include CSS files -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Include FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Include Custom CSS -->
    <link rel="stylesheet" href="/AcadMeter/public/assets/css/teacher_dashboard.css">
    <style>
        /* Optional: Ensure dropdown menus are visible */
        .dropdown-menu {
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="/AcadMeter/public/assets/img/acadmeter_logo.png" alt="AcadMeter Logo" class="logo">
                <h3>AcadMeter</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="?view=dashboard_overview" class="nav-link <?php echo $view === 'dashboard_overview' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="?view=class_management" class="nav-link <?php echo $view === 'class_management' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Class Management</span>
                </a>
                <a href="?view=grade_management" class="nav-link <?php echo $view === 'grade_management' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Grade Management</span>
                </a>
                <a href="?view=performance_monitoring" class="nav-link <?php echo $view === 'performance_monitoring' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Performance Monitoring</span>
                </a>
                <a href="?view=predictive_analytics" class="nav-link <?php echo $view === 'predictive_analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-brain"></i>
                    <span>Predictive Analytics</span>
                </a>
                <a href="?view=feedback" class="nav-link <?php echo $view === 'feedback' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i>
                    <span>Feedback</span>
                </a>
                <a href="?view=reporting" class="nav-link <?php echo $view === 'reporting' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Reporting</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar d-flex justify-content-between align-items-center p-3">
                <button id="sidebar-toggle" class="btn btn-outline-secondary" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h2 id="section-title"><?php echo ucfirst(str_replace('_', ' ', $view)); ?></h2>
                <div class="user-actions d-flex align-items-center">
                    <!-- Notifications Dropdown -->
                    <div class="dropdown mr-3">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="notificationIcon" aria-label="Notifications" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span id="notification-count" class="badge badge-pill badge-danger">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notificationIcon">
                            <h6 class="dropdown-header">Notifications</h6>
                            <div id="notification-items" class="notification-items">
                                <!-- Notifications will be dynamically loaded here -->
                                <p class="dropdown-item">No new notifications.</p>
                            </div>
                        </div>
                    </div>
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" id="profileIcon" aria-label="User Menu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img src="https://ui-avatars.com/api/?name=Teacher&background=0D8ABC&color=fff" alt="User Avatar" class="user-avatar mr-2" style="width:32px; height:32px; border-radius:50%;">
                            <span>Teacher</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="profileIcon">
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/AcadMeter/server/controllers/logout.php" class="dropdown-item" id="logoutButton">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <section class="content-section p-4">
                <?php include("views/{$view}.php"); ?>
            </section>
        </main>
    </div>

    <!-- Include JS files -->
    <!-- Include jQuery first -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Include Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <!-- Include Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Include Chart.js (if needed) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Include Custom JS -->
    <script src="/AcadMeter/public/assets/js/class_management.js"></script>
    <script src="/AcadMeter/public/assets/js/teacher_dashboard.js"></script>

    <!-- Initialize tooltips and other JavaScript functionalities -->
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Function to filter options in a select element
        function filterOptions(selectId, searchInputId) {
            const searchValue = document.getElementById(searchInputId).value.toLowerCase();
            const select = document.getElementById(selectId);
            for (let i = 0; i < select.options.length; i++) {
                const optionText = select.options[i].text.toLowerCase();
                select.options[i].style.display = optionText.includes(searchValue) ? 'block' : 'none';
            }
        }
    </script>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header" id="messageModalHeader">
            <h5 class="modal-title" id="messageModalLabel">Modal Title</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" id="messageModalBody">
            Modal body content goes here.
          </div>
          <div class="modal-footer" id="messageModalFooter">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <!-- Optional additional buttons can be added here -->
          </div>
        </div>
      </div>
    </div>
</body>
</html>
