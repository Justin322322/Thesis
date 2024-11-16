<?php
$current_page = 'performance_monitoring';
?>

<div class="container mt-4">
    <div id="performance-monitoring" class="content-section">
        <h2 class="mb-4">Performance Monitoring <i class="fas fa-info-circle text-primary" data-toggle="tooltip" title="Monitor student performance metrics."></i></h2>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Class Performance Summary</h5>
                    </div>
                    <div class="card-body chart-container">
                        <canvas id="classPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Section Performance Breakdown</h5>
                    </div>
                    <div class="card-body chart-container">
                        <canvas id="sectionSummaryChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Monthly Performance Trends</h5>
                    </div>
                    <div class="card-body chart-container">
                        <canvas id="performanceTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let classPerformanceChart, sectionSummaryChart, performanceTrendChart;

function initializeCharts() {
    if (classPerformanceChart) classPerformanceChart.destroy();
    if (sectionSummaryChart) sectionSummaryChart.destroy();
    if (performanceTrendChart) performanceTrendChart.destroy();

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
        },
    };

    classPerformanceChart = new Chart(document.getElementById('classPerformanceChart'), {
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
            ...commonOptions,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    sectionSummaryChart = new Chart(document.getElementById('sectionSummaryChart'), {
        type: 'pie',
        data: {
            labels: ['A', 'B', 'C', 'D', 'F'],
            datasets: [{
                data: [30, 25, 20, 15, 10],
                backgroundColor: ['#4CAF50', '#FF6384', '#FFCE56', '#36A2EB', '#9966FF']
            }]
        },
        options: commonOptions
    });

    performanceTrendChart = new Chart(document.getElementById('performanceTrendChart'), {
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
            ...commonOptions,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', initializeCharts);

function updateCharts(newData) {
    classPerformanceChart.data.labels = newData.classPerformance.labels;
    classPerformanceChart.data.datasets[0].data = newData.classPerformance.data;
    classPerformanceChart.update();

    sectionSummaryChart.data.datasets[0].data = newData.sectionSummary;
    sectionSummaryChart.update();

    performanceTrendChart.data.labels = newData.performanceTrend.labels;
    performanceTrendChart.data.datasets[0].data = newData.performanceTrend.data;
    performanceTrendChart.update();
}

window.addEventListener('resize', function() {
    classPerformanceChart.resize();
    sectionSummaryChart.resize();
    performanceTrendChart.resize();
});
</script>

