// performance_monitoring.js

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Load initial data
    loadClassPerformanceChart();
    loadSectionSummaryChart();
    loadClassStandings();

    // Update charts when section is changed
    document.getElementById('sectionSelect').addEventListener('change', function() {
        loadSectionSummaryChart();
        loadClassStandings();
    });
});

let classPerformanceChart, sectionSummaryChart;

function loadClassPerformanceChart() {
    fetch('/AcadMeter/server/controllers/get_class_performance.php')
        .then(response => response.json())
        .then(data => {
            const classLabels = data.map(item => item.section_name);
            const classData = data.map(item => item.average_score);

            if (classPerformanceChart) classPerformanceChart.destroy();

            classPerformanceChart = new Chart(document.getElementById('classPerformanceChart'), {
                type: 'bar',
                data: {
                    labels: classLabels,
                    datasets: [{
                        label: 'Average Score',
                        data: classData,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Average Score: ${context.parsed.y}`;
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading class performance data:', error));
}

function loadSectionSummaryChart() {
    const sectionId = document.getElementById('sectionSelect').value;

    fetch('/AcadMeter/server/controllers/get_section_summary.php?section_id=' + sectionId)
        .then(response => response.json())
        .then(data => {
            const labels = Object.keys(data);
            const values = Object.values(data);

            if (sectionSummaryChart) sectionSummaryChart.destroy();

            sectionSummaryChart = new Chart(document.getElementById('sectionSummaryChart'), {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#4CAF50', '#FF6384', '#FFCE56', '#36A2EB', '#9966FF']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.chart._metasets[context.datasetIndex].total;
                                    const percentage = ((value / total) * 100).toFixed(2);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading section summary data:', error));
}

function loadClassStandings() {
    const sectionId = document.getElementById('sectionSelect').value;

    fetch('/AcadMeter/server/controllers/get_class_standings.php?section_id=' + sectionId)
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#classStandingsTable tbody');
            tbody.innerHTML = '';

            if (data.length > 0) {
                data.forEach((student, index) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${student.student_name}</td>
                        <td>${student.average_grade}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="3" class="text-center">No data available</td>';
                tbody.appendChild(tr);
            }
        })
        .catch(error => console.error('Error loading class standings:', error));
}

window.addEventListener('resize', function() {
    if (classPerformanceChart) classPerformanceChart.resize();
    if (sectionSummaryChart) sectionSummaryChart.resize();
});
