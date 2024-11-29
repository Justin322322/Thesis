// File: public/assets/js/predictive_analytics.js

$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Fetch at-risk students on page load
    fetchAtRiskStudents();

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
                const row = `
                    <tr>
                        <td>${student.student_id}</td>
                        <td>${student.name}</td>
                        <td>${student.average_final_grade}</td>
                        <td>${student.risk_probability}</td>
                        <td>${student.suggested_intervention}</td>
                    </tr>
                `;
                tableBody.append(row);
            });
        } else {
            tableBody.append('<tr><td colspan="5" class="text-center text-muted">No at-risk students identified.</td></tr>');
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
