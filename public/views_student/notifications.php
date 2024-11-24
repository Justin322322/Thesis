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
    require_once __DIR__ . '/../../server/controllers/student_dashboard_controller.php';
    $studentDashboardController = new StudentDashboardController($conn);
}

$studentId = $_SESSION['user_id'];
$notifications = $studentDashboardController->getNotifications($studentId);
?>

<div class="notifications-view">
    <h2>Notifications</h2>
    <?php if (!empty($notifications)): ?>
        <div class="list-group">
            <?php foreach ($notifications as $notification): ?>
                <div class="list-group-item">
                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                    <small class="text-muted"><?php echo htmlspecialchars($notification['created_at']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No notifications available.</p>
    <?php endif; ?>
</div>

