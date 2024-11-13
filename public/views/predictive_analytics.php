<!-- C:\xampp\htdocs\AcadMeter\public\views\predictive_analytics.php -->
<div id="predictive-analytics" class="content-section">
    <h2>Predictive Analytics <i class="fas fa-info-circle" data-toggle="tooltip" title="Identify students likely to fail and take action to improve their outcomes."></i></h2>

    <!-- At-Risk Students Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            At-Risk Students <i class="fas fa-exclamation-triangle text-warning"></i>
        </div>
        <div class="card-body">
            <p class="text-muted">Students flagged by the predictive model as at-risk of failing. Consider providing additional support or interventions.</p>
            <table class="table table-bordered" id="atRiskTable">
                <thead class="thead-light">
                    <tr>
                        <th>Student Name</th>
                        <th>Current Grade</th>
                        <th>Risk Probability</th>
                        <th>Suggested Intervention</th>
                    </tr>
                </thead>
                <tbody id="atRiskTableBody">
                    <!-- Data will be dynamically populated here via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Explanation Section -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> The predictive model analyzes current grades, participation, and assessment trends to estimate the likelihood of a student failing. Consider implementing the suggested interventions to improve their performance.
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Fetch at-risk students from the server
    fetch('/AcadMeter/server/controllers/predictive_analytics_controller.php')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById("atRiskTableBody");
            tableBody.innerHTML = ""; // Clear previous content if any
            
            if (data.at_risk_students && data.at_risk_students.length > 0) {
                data.at_risk_students.forEach(student => {
                    const row = document.createElement("tr");

                    row.innerHTML = `
                        <td>${student.name}</td>
                        <td>${student.current_grade}%</td>
                        <td>${student.risk_probability}%</td>
                        <td>${student.suggested_intervention}</td>
                    `;
                    tableBody.appendChild(row);
                });
            } else {
                // Show message if no at-risk students are found
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No at-risk students identified.</td></tr>';
            }
        })
        .catch(error => {
            console.error("Error fetching data:", error);
            document.getElementById("atRiskTableBody").innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading data.</td></tr>';
        });
});
</script>
