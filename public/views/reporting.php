<?php
// public/views/reporting.php
?>
<div id="reporting" class="content-section">
    <h2>Reporting <i class="fas fa-info-circle" data-toggle="tooltip" title="Generate detailed academic reports and summaries."></i></h2>

    <!-- Grade Reports -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            Grade Reports
            <button class="btn btn-primary btn-sm float-right" onclick="generateGradeReport()"><i class="fas fa-file-download"></i> Download Report</button>
        </div>
        <div class="card-body">
            <p>Generate reports by quarter or semester, covering quizzes, assignments, extracurriculars, and exams.</p>
        </div>
    </div>

    <!-- Attendance Reports -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            Attendance Reports
            <button class="btn btn-primary btn-sm float-right" onclick="generateAttendanceReport()"><i class="fas fa-file-download"></i> Download Report</button>
        </div>
        <div class="card-body">
            <p>Track and report on attendance, including excused and unexcused absences.</p>
        </div>
    </div>

    <!-- Performance Analysis -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            Performance Analysis
            <button class="btn btn-primary btn-sm float-right" onclick="generatePerformanceAnalysis()"><i class="fas fa-file-download"></i> Download Analysis</button>
        </div>
        <div class="card-body">
            <p>Analyze student performance trends to highlight improvement areas or challenges.</p>
        </div>
    </div>

    <!-- At-Risk Students Report -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            Predictive Analytics - High-Risk Students
            <button class="btn btn-warning btn-sm float-right" onclick="generateAtRiskReport()"><i class="fas fa-file-download"></i> Download At-Risk Report</button>
        </div>
        <div class="card-body">
            <p>Identify students who may need additional support to meet academic goals.</p>
        </div>
    </div>
</div>

<script>
function generateGradeReport() { alert("Generating Grade Report..."); }
function generateAttendanceReport() { alert("Generating Attendance Report..."); }
function generatePerformanceAnalysis() { alert("Generating Performance Analysis..."); }
function generateAtRiskReport() { alert("Generating At-Risk Report..."); }
</script>
