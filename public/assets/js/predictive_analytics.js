// public/assets/js/predictive_analytics.js
$(document).ready(function () {
    // Fetch At-Risk Students Data for Predictive Analytics
    fetch('/api/at_risk_students')
        .then(response => response.json())
        .then(data => {
            const tbody = $("#predictive-analytics tbody");
            tbody.empty();

            data.forEach(student => {
                const row = `
                    <tr>
                        <td>${student.name}</td>
                        <td>${student.current_grade}%</td>
                        <td>${student.risk_probability > 0.8 ? 'High' : 'Moderate'} (${Math.round(student.risk_probability * 100)}%)</td>
                        <td>${student.suggested_intervention}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        })
        .catch(error => console.error('Error fetching at-risk students:', error));
});
