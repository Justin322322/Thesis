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
$performanceData = $studentDashboardController->getPerformanceData($userId);
?>

<div class="performance-view">
    <h2>Performance Overview</h2>
    <?php if (!empty($performanceData)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Average Grade</th>
                        <th>Passing Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($performanceData as $subject): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            <td><?php echo number_format($subject['average_grade'], 2); ?></td>
                            <td><?php echo number_format($subject['passing_grade'], 2); ?></td>
                            <td>
                                <?php if (floatval($subject['average_grade']) >= floatval($subject['passing_grade'])): ?>
                                    <span class="badge bg-success">Pass</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Fail</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <h3>Performance Chart</h3>
            <canvas id="performanceChart"></canvas>
        </div>
    <?php else: ?>
        <p>No performance data available.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('performanceChart').getContext('2d');
    var performanceData = <?php echo json_encode($performanceData); ?>;
    
    var labels = performanceData.map(item => item.subject_name);
    var averageGrades = performanceData.map(item => parseFloat(item.average_grade));
    var passingGrades = performanceData.map(item => parseFloat(item.passing_grade));

    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Average Grade',
                    data: averageGrades,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Passing Grade',
                    data: passingGrades,
                    type: 'line',
                    fill: false,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y.toFixed(2);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

