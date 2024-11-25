<?php
// File: public/views/dashboard_overview.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header("Location: /login.html");
    exit();
}

// Include necessary files
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../server/models/DashboardModel.php';

// Initialize DashboardModel
$dashboardModel = new DashboardModel($conn);

// Fetch metrics
try {
    $totalStudents = $dashboardModel->getTotalStudents();
    $atRiskStudents = $dashboardModel->getAtRiskStudentsCount();
    $performanceData = $dashboardModel->getAveragePerformanceByMonth();
} catch (Exception $e) {
    $errorMessage = "An error occurred while fetching dashboard data: " . $e->getMessage();
}
?>

<style>
.card {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
.bg-primary {
    background-color: #007bff !important;
}
.bg-warning {
    background-color: #ffc107 !important;
    color: #212529;
}
.bg-success {
    background-color: #28a745 !important;
}
.card-text {
    font-size: 2.5rem;
    font-weight: bold;
}
.text-muted {
    color: rgba(0,0,0,0.6) !important;
}
</style>

<div class="dashboard-overview">
    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errorMessage); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Total Students Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Students</h5>
                    <p class="card-text" id="totalStudents">
                        <?php echo isset($totalStudents) ? $totalStudents : '<i class="fas fa-spinner fa-spin"></i> Loading...'; ?>
                    </p>
                    <p class="text-muted">Number of student's across all section</p>
                </div>
            </div>
        </div>
        <!-- At-Risk Students Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 bg-warning">
                <div class="card-body">
                    <h5 class="card-title">At-Risk Students</h5>
                    <p class="card-text" id="atRiskStudents">
                        <?php 
                        if (isset($atRiskStudents) && isset($totalStudents)) {
                            echo $atRiskStudents;
                            $percentage = $totalStudents > 0 ? ($atRiskStudents / $totalStudents) * 100 : 0;
                            echo " <span class='text-muted'>(" . number_format($percentage, 1) . "%)</span>";
                        } else {
                            echo '<i class="fas fa-spinner fa-spin"></i> Loading...';
                        }
                        ?>
                    </p>
                    <p class="text-muted">Students with average grade lower than 75%</p>
                    <p class="text-muted">Please check Predictive Analytics tab for more info.</p>
                </div>
            </div>
        </div>
        <!-- Active Classes Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Classes</h5>
                    <p class="card-text" id="activeClasses">
                        <?php echo isset($activeClasses) ? $activeClasses : '<i class="fas fa-spinner fa-spin"></i> Loading...'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Monthly Average Performance</h5>
                    <div class="chart-container" style="position: relative; height:50vh; width:100%;">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($performanceData, 'month')); ?>,
            datasets: [{
                label: 'Average Grade',
                data: <?php echo json_encode(array_column($performanceData, 'average_grade')); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.6)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Monthly Average Performance',
                    font: {
                        size: 18,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Average Grade: ' + context.parsed.y.toFixed(2) + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>

