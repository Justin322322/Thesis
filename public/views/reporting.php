<?php
// reporting.php

$current_page = 'reporting';

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.php');
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
    <title>Reporting</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Optional: Your custom CSS -->
    <link rel="stylesheet" href="/AcadMeter/public/assets/css/styles.css">
</head>
<body>
    <!-- Include your navigation bar here -->

    <div class="container mt-4">
        <div id="reporting" class="content-section">
            <h2 class="mb-4">Reporting <i class="fas fa-info-circle text-primary" data-bs-toggle="tooltip" title="Generate detailed academic reports and summaries."></i></h2>

            <!-- Section Selection -->
            <div class="mb-4">
                <label for="sectionSelect" class="form-label">Select Section:</label>
                <select id="sectionSelect" class="form-select" aria-label="Select Section">
                    <option value="">-- Select Section --</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= $section['section_id'] ?>"><?= htmlspecialchars($section['section_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <!-- Grade Reports -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Grade Reports</h5>
                            <button class="btn btn-light btn-sm" onclick="generateGradeReport()">
                                <i class="fas fa-file-download"></i> Download Report
                            </button>
                        </div>
                        <div class="card-body">
                            <p>Generate reports by quarter or semester, covering quizzes, assignments, extracurriculars, and exams.</p>
                        </div>
                    </div>
                </div>
                <!-- Attendance Reports -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Attendance Reports</h5>
                            <button class="btn btn-light btn-sm" onclick="generateAttendanceReport()">
                                <i class="fas fa-file-download"></i> Download Report
                            </button>
                        </div>
                        <div class="card-body">
                            <p>Track and report on attendance, including excused and unexcused absences.</p>
                        </div>
                    </div>
                </div>
                <!-- Performance Analysis -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Performance Analysis</h5>
                            <button class="btn btn-light btn-sm" onclick="generatePerformanceAnalysis()">
                                <i class="fas fa-file-download"></i> Download Analysis
                            </button>
                        </div>
                        <div class="card-body">
                            <p>Analyze student performance trends to highlight improvement areas or challenges.</p>
                        </div>
                    </div>
                </div>
                <!-- At-Risk Students Report -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Predictive Analytics - High-Risk Students</h5>
                            <button class="btn btn-light btn-sm" onclick="generateAtRiskReport()">
                                <i class="fas fa-file-download"></i> Download At-Risk Report
                            </button>
                        </div>
                        <div class="card-body">
                            <p>Identify students who may need additional support to meet academic goals.</p>
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
    <!-- Include jQuery (optional, if needed) -->
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <!-- Reporting JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Ensure a section is selected before generating reports
        function getSelectedSection() {
            const sectionSelect = document.getElementById('sectionSelect');
            const sectionId = sectionSelect.value;
            if (!sectionId) {
                alert('Please select a section first.');
                return null;
            }
            return sectionId;
        }

        window.generateGradeReport = function() {
            const sectionId = getSelectedSection();
            if (!sectionId) return;

            // Implement actual report generation logic here
            window.location.href = `/AcadMeter/server/controllers/generate_grade_report.php?section_id=${sectionId}`;
        }

        window.generateAttendanceReport = function() {
            const sectionId = getSelectedSection();
            if (!sectionId) return;

            // Implement actual report generation logic here
            window.location.href = `/AcadMeter/server/controllers/generate_attendance_report.php?section_id=${sectionId}`;
        }

        window.generatePerformanceAnalysis = function() {
            const sectionId = getSelectedSection();
            if (!sectionId) return;

            // Implement actual analysis generation logic here
            window.location.href = `/AcadMeter/server/controllers/generate_performance_analysis.php?section_id=${sectionId}`;
        }

        window.generateAtRiskReport = function() {
            const sectionId = getSelectedSection();
            if (!sectionId) return;

            // Implement actual report generation logic here
            window.location.href = `/AcadMeter/server/controllers/generate_at_risk_report.php?section_id=${sectionId}`;
        }
    });
    </script>
</body>
</html>
