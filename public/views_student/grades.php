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

$userId = $_SESSION['user_id'];
$detailedGrades = $studentDashboardController->getDetailedGrades($userId);
?>

<div class="grades-view">
    <h2>Detailed Grades</h2>
    <?php if (!empty($detailedGrades)): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Grade</th>
                    <th>Component</th>
                    <th>Quarter</th>
                    <th>Academic Year</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detailedGrades as $grade): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                        <td><?php echo htmlspecialchars($grade['component_name']); ?></td>
                        <td><?php echo htmlspecialchars($grade['quarter']); ?></td>
                        <td><?php echo htmlspecialchars($grade['academic_year']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No grades available.</p>
    <?php endif; ?>
</div>

