<?php
// performance_monitoring.php

$current_page = 'performance_monitoring';

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.html');
    exit;
}

// Get the instructor_id from the session
$instructor_id = $_SESSION['user_id'];

// Fetch list of sections taught by the instructor
$stmt = $conn->prepare("
    SELECT section_id, section_name
    FROM sections
    WHERE instructor_id = ?
");
if (!$stmt) {
    die('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
}
$stmt->bind_param('i', $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[] = [
        'section_id' => $row['section_id'],
        'section_name' => $row['section_name']
    ];
}
$stmt->close();

// Close the connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Performance Monitoring</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome CSS (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Optional: Your custom CSS -->
    <link rel="stylesheet" href="/AcadMeter/public/assets/css/styles.css">
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div id="performance-monitoring" class="content-section">
            <h2 class="mb-4">Performance Monitoring <i class="fas fa-info-circle text-primary" data-bs-toggle="tooltip" title="Monitor student performance metrics."></i></h2>

            <!-- Section Selection -->
            <div class="mb-4">
                <label for="sectionSelect" class="form-label">Select Section:</label>
                <select id="sectionSelect" class="form-select" aria-label="Select Section">
                    <option value="0">All Sections</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= $section['section_id'] ?>"><?= htmlspecialchars($section['section_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <!-- Class Performance Summary -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Class Performance Summary</h5>
                        </div>
                        <div class="card-body chart-container">
                            <canvas id="classPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Section Performance Breakdown -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Section Performance Breakdown</h5>
                        </div>
                        <div class="card-body chart-container">
                            <canvas id="sectionSummaryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Standings -->
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Class Standings</h5>
                        </div>
                        <div class="card-body">
                            <table id="classStandingsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student Name</th>
                                        <th>Average Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS (for tooltips) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS (for icons) -->
    <!-- Already included in the head section -->
    <!-- Include jQuery (required for AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Performance Monitoring JS -->
    <script src="/AcadMeter/public/assets/js/performance_monitoring.js"></script>
</body>
</html>
<!-- No changes needed as grade categories are handled in JavaScript and PHP backend -->
