<?php
ob_start();
// File: C:\xampp\htdocs\AcadMeter\public\student_dashboard.php

// Start the session at the very beginning of the script
session_start();

// Define a constant to allow access to controller
define('IN_STUDENT_DASHBOARD', true);

// Include the centralized initialization file using absolute path
require_once __DIR__ . '/../config/init.php';

// Redirect to login if not authenticated as Student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Student') {
  header('Location: /AcadMeter/public/login.html');
  exit;
}

// Include the controller
require_once __DIR__ . '/../server/controllers/student_dashboard_controller.php';
if (!isset($studentDashboardController)) {
  die("Failed to initialize StudentDashboardController");
}

// Set default view to `dashboard_overview`
$allowed_views = ['dashboard_overview', 'grades', 'performance', 'feedback', 'notifications', 'evaluation'];
$view = isset($_GET['view']) && in_array($_GET['view'], $allowed_views) ? $_GET['view'] : 'dashboard_overview';

// Ensure the view file exists
$view_file = __DIR__ . "/views_student/{$view}.php";
if (!file_exists($view_file)) {
  $view = 'dashboard_overview';
  $view_file = __DIR__ . "/views_student/{$view}.php";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard - AcadMeter</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Include CSS files -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Include FontAwesome CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Include Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Include Custom CSS -->
  <link rel="stylesheet" href="/AcadMeter/public/assets/css/styles.css">
  <link rel="stylesheet" href="/AcadMeter/public/assets/css/student_dashboard.css">
</head>
<body>
  <!-- Toast container for notifications -->
  <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

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
              <a href="?view=grades" class="nav-link <?php echo $view === 'grades' ? 'active' : ''; ?>">
                  <i class="fas fa-chart-bar"></i>
                  <span>Grades</span>
              </a>
              <a href="?view=performance" class="nav-link <?php echo $view === 'performance' ? 'active' : ''; ?>">
                  <i class="fas fa-chart-line"></i>
                  <span>Performance</span>
              </a>
              <a href="?view=feedback" class="nav-link <?php echo $view === 'feedback' ? 'active' : ''; ?>">
                  <i class="fas fa-comments"></i>
                  <span>Feedback</span>
              </a>
              <a href="?view=notifications" class="nav-link <?php echo $view === 'notifications' ? 'active' : ''; ?>">
                  <i class="fas fa-bell"></i>
                  <span>Notifications</span>
              </a>
              <a href="?view=evaluation" class="nav-link <?php echo $view === 'evaluation' ? 'active' : ''; ?>">
                  <i class="fas fa-user-graduate"></i>
                  <span>Teacher Evaluation</span>
              </a>
          </nav>
      </aside>

      <main class="main-content">
          <header class="top-bar d-flex justify-content-between align-items-center p-3">
              <button id="sidebar-toggle" class="btn btn-outline-secondary" aria-label="Toggle Sidebar">
                  <i class="fas fa-bars"></i>
              </button>
              <h2 id="section-title">
                  <?php echo ucfirst(str_replace('_', ' ', $view)); ?>
              </h2>
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
                          <img src="https://ui-avatars.com/api/?name=Student&background=0D8ABC&color=fff" alt="User Avatar" class="user-avatar mr-2" style="width:32px; height:32px; border-radius:50%;">
                          <span>Student</span>
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

          <section id="content-section" class="content-section p-4">
              <?php include($view_file); ?>
          </section>
      </main>
  </div>

  <!-- Message Modal -->
  <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="messageModalTitle">Notification</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body" id="messageModalBody">
                  <!-- Message will be inserted here -->
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
          </div>
      </div>
  </div>

  <!-- Include JS files -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="/AcadMeter/public/assets/js/student_dashboard.js"></script>

  <script>
      $(function () {
          $('[data-toggle="tooltip"]').tooltip();

          // Toggle sidebar
          $('#sidebar-toggle').click(function() {
              $('.sidebar').toggleClass('collapsed');
              $('.main-content').toggleClass('expanded');
          });
      });

      // Function to show messages in the modal
      function showMessage(message, isError = false) {
          const modalTitle = document.getElementById('messageModalTitle');
          const modalBody = document.getElementById('messageModalBody');
          
          modalTitle.textContent = isError ? 'Error' : 'Success';
          modalBody.textContent = message;
          
          const modal = new bootstrap.Modal(document.getElementById('messageModal'));
          modal.show();

          // Auto-hide after 5 seconds
          setTimeout(() => {
              modal.hide();
          }, 5000);
      }
  </script>
</body>
</html>

