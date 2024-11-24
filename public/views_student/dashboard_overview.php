<?php
// Start the session if it hasn't been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Student') {
    echo "You must be logged in as a student to view this page.";
    exit;
}

if (!defined('IN_STUDENT_DASHBOARD')) {
    define('IN_STUDENT_DASHBOARD', true);
}

require_once __DIR__ . '/../../server/controllers/student_dashboard_controller.php';

// Ensure $conn is available
if (!isset($conn)) {
    require_once __DIR__ . '/../../config/db_connection.php';
}

$studentDashboardController = new StudentDashboardController($conn);

// Fetch necessary data
$studentId = $_SESSION['user_id'];
$gradeSummary = $studentDashboardController->getGradeSummary($studentId);
$recentFeedback = $studentDashboardController->getRecentFeedback($studentId);
$performanceData = $studentDashboardController->getPerformanceData($studentId);
$recentNotifications = $studentDashboardController->getNotifications($studentId);
?>

<div class="dashboard-overview">
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Grade Summary</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($gradeSummary)): ?>
                        <ul class="list-group">
                            <?php foreach ($gradeSummary as $grade): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($grade['subject_name']); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($grade['grade']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No grades available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Feedback</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentFeedback)): ?>
                        <ul class="list-group">
                            <?php foreach ($recentFeedback as $feedback): ?>
                                <li class="list-group-item">
                                    <h6><?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?></h6>
                                    <p><?php echo htmlspecialchars($feedback['feedback_message']); ?></p>
                                    <small class="text-muted"><?php echo htmlspecialchars($feedback['created_at']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No recent feedback.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performance Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Notifications</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentNotifications)): ?>
                        <ul class="list-group">
                            <?php foreach ($recentNotifications as $notification): ?>
                                <li class="list-group-item">
                                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted"><?php echo htmlspecialchars($notification['created_at']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No recent notifications.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var ctx = document.getElementById('performanceChart').getContext('2d');
  var performanceData = <?php echo json_encode($performanceData); ?>;
  
  var labels = performanceData.map(item => item.subject_name);
  var averageGrades = performanceData.map(item => parseFloat(item.average_grade));

  var chart = new Chart(ctx, {
      type: 'bar',
      data: {
          labels: labels,
          datasets: [{
              label: 'Average Grade',
              data: averageGrades,
              backgroundColor: 'rgba(75, 192, 192, 0.6)',
              borderColor: 'rgba(75, 192, 192, 1)',
              borderWidth: 1
          }]
      },
      options: {
          responsive: true,
          scales: {
              y: {
                  beginAtZero: true,
                  max: 100
              }
          }
      }
  });
});
</script>

