<?php
// File: C:\xampp\htdocs\AcadMeter\public\views\predictive_analytics.php

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is authenticated and has the Instructor role
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.html');
    exit;
}

// Embed necessary data (if any)
// If you have additional data to pass to JavaScript, embed them here
// For example, components and subcategories can be fetched and passed if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predictive Analytics</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="/AcadMeter/public/assets/css/styles.css">
    <style>
        .content-section {
            padding: 20px;
        }
        .alert-info {
            margin-top: 20px;
        }
        /* Optional: Add styling for better visualization */
        #loadingSpinner {
            display: none;
        }
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        .subject-details {
            margin-top: 20px;
        }
        .subject-detail-card {
            margin-bottom: 10px;
        }
        .subject-detail-card .card-body {
            padding: 10px;
        }
        .subject-detail-card .card-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .subject-detail-card .card-text {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div id="predictive-analytics" class="content-section">
        <h2 class="mb-4"><i class="fas fa-info-circle text-primary" data-toggle="tooltip" title="Identify students likely to fail and take action to improve their outcomes."></i> Predictive Analytics</h2>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="text-center mb-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p>Fetching predictive analytics data...</p>
        </div>

        <!-- At-Risk Students Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> At-Risk Students</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Students flagged by the predictive model as at-risk of failing. Consider providing additional support or interventions.</p>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="atRiskTable">
                        <thead class="table-light">
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Average Final Grade (%)</th>
                                <th>Risk Probability (%)</th>
                                <th>Suggested Intervention</th>
                                <th>Failing Subjects</th>
                            </tr>
                        </thead>
                        <tbody id="atRiskTableBody">
                            <!-- Data will be dynamically populated here via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <!-- At-Risk Students Chart -->
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> At-Risk Students Chart</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="atRiskChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <!-- Subjects Impacting Failing Students Chart -->
                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Subjects Impacting Failing Students</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="subjectsImpactChart"></canvas>
                        </div>
                        <div id="subjectDetails" class="subject-details"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4">
            <i class="fas fa-info-circle"></i> The predictive model analyzes current grades and assessment trends to estimate the likelihood of a student failing. Consider implementing the suggested interventions to improve their performance.
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Fetch at-risk students on page load
        fetchAtRiskStudents();

        // Optionally, set up periodic fetching for real-time updates (e.g., every 5 minutes)
        // setInterval(fetchAtRiskStudents, 300000); // 300,000 ms = 5 minutes

        // Function to fetch at-risk students via AJAX
        function fetchAtRiskStudents() {
            $('#loadingSpinner').show(); // Show loading spinner
            $.ajax({
                url: '/AcadMeter/server/controllers/PredictiveAnalyticsController.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#loadingSpinner').hide(); // Hide loading spinner
                    if (response.status === 'success') {
                        renderAtRiskTable(response.at_risk_students);
                        renderAtRiskChart(response.at_risk_students);
                        renderSubjectsImpactChart(response.at_risk_students);
                        renderSubjectDetails(response.at_risk_students);
                    } else {
                        showError('Error fetching predictive analytics: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#loadingSpinner').hide(); // Hide loading spinner
                    showError('Error fetching predictive analytics data. Please try again.');
                    console.error('AJAX error:', status, error);
                    console.error('Response:', xhr.responseText);
                }
            });
        }

        // Function to render the At-Risk Students table
        function renderAtRiskTable(students) {
            const tableBody = $('#atRiskTableBody');
            tableBody.empty();

            if (students.length > 0) {
                $.each(students, function(index, student) {
                    // Join failing subjects with commas or display as a list
                    const failingSubjects = student.failing_subjects.join(', ');

                    const row = `
                        <tr>
                            <td>${student.student_id}</td>
                            <td>${student.name}</td>
                            <td>${student.average_final_grade}</td>
                            <td>${student.risk_probability}</td>
                            <td>${student.suggested_intervention}</td>
                            <td>${failingSubjects}</td> <!-- Display Failing Subjects -->
                        </tr>
                    `;
                    tableBody.append(row);
                });
            } else {
                tableBody.append('<tr><td colspan="6" class="text-center text-muted">No at-risk students identified.</td></tr>');
            }
        }

        // Function to render the At-Risk Students chart
        function renderAtRiskChart(students) {
            const ctx = document.getElementById('atRiskChart').getContext('2d');
            const labels = students.map(student => student.name);
            const data = students.map(student => student.risk_probability);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Risk Probability (%)',
                        data: data,
                        backgroundColor: data.map(value => {
                            if (value >= 75) return 'rgba(255, 0, 0, 0.8)'; // High risk
                            if (value >= 50) return 'rgba(255, 165, 0, 0.8)'; // Medium risk
                            return 'rgba(0, 128, 0, 0.8)'; // Low risk
                        }),
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Function to render the Subjects Impacting Failing Students chart
        function renderSubjectsImpactChart(students) {
            const ctx = document.getElementById('subjectsImpactChart').getContext('2d');
            const subjects = {};

            students.forEach(student => {
                student.failing_subjects.forEach(subject => {
                    if (!subjects[subject]) {
                        subjects[subject] = [];
                    }
                    subjects[subject].push(student);
                });
            });

            const labels = Object.keys(subjects);
            const data = labels.map(label => subjects[label].length);

            new Chart(ctx, {
                type: 'polarArea',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Number of Students Failing',
                        data: data,
                        backgroundColor: [
                            'rgba(255, 0, 0, 0.8)', // High risk
                            'rgba(255, 165, 0, 0.8)', // Medium risk
                            'rgba(0, 128, 0, 0.8)' // Low risk
                        ],
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ${value} students`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Function to render the subject details
        function renderSubjectDetails(students) {
            const subjects = {};

            students.forEach(student => {
                student.failing_subjects.forEach(subject => {
                    if (!subjects[subject]) {
                        subjects[subject] = [];
                    }
                    subjects[subject].push(student);
                });
            });

            const subjectDetails = $('#subjectDetails');
            subjectDetails.empty();

            Object.keys(subjects).forEach(subject => {
                const subjectDiv = $('<div>').addClass('card subject-detail-card');
                const subjectCardBody = $('<div>').addClass('card-body');
                const subjectTitle = $('<h6>').addClass('card-title').text(subject);
                subjectCardBody.append(subjectTitle);

                subjects[subject].forEach(student => {
                    const studentDetail = $('<p>').addClass('card-text').text(`${student.name} (Section: ${student.section}, ${student.average_final_grade}%)`);
                    subjectCardBody.append(studentDetail);
                });

                subjectDiv.append(subjectCardBody);
                subjectDetails.append(subjectDiv);
            });
        }

        // Function to display error messages
        function showError(message) {
            const alertDiv = $(`
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `);
            $('#predictive-analytics').prepend(alertDiv);
        }
    });
    </script>
</body>
</html>

