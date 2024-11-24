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
