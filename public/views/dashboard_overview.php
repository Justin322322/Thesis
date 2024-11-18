<?php
// File: C:\xampp\htdocs\AcadMeter\public\views\dashboard_overview.php

// Example: Fetch metrics from the database or use existing data
// For demonstration, we'll mock some data
$total_students = 120;
$at_risk_students = 5;
$active_classes = 8;
?>

<div class="container-fluid">
    <div class="row">
        <!-- Total Students Card -->
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Students</h5>
                    <p class="card-text" id="totalStudents"><?= htmlspecialchars($total_students) ?></p>
                </div>
            </div>
        </div>
        <!-- At-Risk Students Card -->
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">At-Risk Students</h5>
                    <p class="card-text" id="atRiskStudents"><?= htmlspecialchars($at_risk_students) ?></p>
                </div>
            </div>
        </div>
        <!-- Active Classes Card -->
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Active Classes</h5>
                    <p class="card-text" id="activeClasses"><?= htmlspecialchars($active_classes) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Example Chart -->
    <div class="row">
        <div class="col-md-12">
            <canvas id="performanceChart" height="100"></canvas>
        </div>
    </div>
</div>

<script>
    // Initialize Chart.js Performance Chart
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June'],
                datasets: [{
                    label: 'Average Performance',
                    data: [65, 59, 80, 81, 56, 55],
                    backgroundColor: 'rgba(74, 144, 226, 0.2)',
                    borderColor: 'rgba(74, 144, 226, 1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>
