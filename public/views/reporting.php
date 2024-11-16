<div id="reporting" class="content-section">
    <h2 class="mb-4">Reporting <i class="fas fa-info-circle text-primary" data-toggle="tooltip" title="Generate detailed academic reports and summaries."></i></h2>

    <div class="row">
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

<script>
function generateGradeReport() {
    alert("Generating Grade Report...");
    // Implement actual report generation logic here
}

function generateAttendanceReport() {
    alert("Generating Attendance Report...");
    // Implement actual report generation logic here
}

function generatePerformanceAnalysis() {
    alert("Generating Performance Analysis...");
    // Implement actual analysis generation logic here
}

function generateAtRiskReport() {
    alert("Generating At-Risk Report...");
    // Implement actual report generation logic here
}
</script>