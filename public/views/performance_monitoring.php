<?php
// public/views/performance_monitoring.php
?>
<div id="performance-monitoring" class="content-section">
    <h2>Performance Monitoring <i class="fas fa-info-circle" data-toggle="tooltip" title="Monitor student performance metrics."></i></h2>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">Class Performance Summary</div>
        <div class="card-body">
            <canvas id="classPerformanceChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">Section Performance Breakdown</div>
        <div class="card-body">
            <canvas id="sectionSummaryChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">Monthly Performance Trends</div>
        <div class="card-body">
            <canvas id="performanceTrendChart" style="max-height: 300px;"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function initializeCharts() {
    new Chart(document.getElementById('classPerformanceChart'), {
        type: 'bar',
        data: {
            labels: ['Section 1', 'Section 2'],
            datasets: [{
                label: 'Average Score',
                data: [85, 78],
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    new Chart(document.getElementById('sectionSummaryChart'), {
        type: 'pie',
        data: {
            labels: ['A', 'B', 'C', 'D', 'F'],
            datasets: [{
                data: [30, 25, 20, 15, 10],
                backgroundColor: ['#4CAF50', '#FF6384', '#FFCE56', '#36A2EB', '#9966FF']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(document.getElementById('performanceTrendChart'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar'],
            datasets: [{
                label: 'Class A',
                data: [75, 80, 85],
                borderColor: '#4CAF50',
                fill: false,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
initializeCharts();
</script>
