<?php
// includes/sidebar.php
?>
<div class="sidebar">
    <img src="../public/assets/img/acadmeter_logo.png" alt="AcadMeter Logo" class="mb-3">
    <h3 class="text-center mb-4">AcadMeter Teacher</h3>
    <a href="teacher_dashboard.php?view=dashboard_overview" class="nav-link sidebar-link <?= (isset($_GET['view']) && $_GET['view'] == 'dashboard_overview') ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard Overview</a>
    <a href="teacher_dashboard.php?view=class_management" class="nav-link sidebar-link <?= (isset($_GET['view']) && $_GET['view'] == 'class_management') ? 'active' : '' ?>"><i class="fas fa-users"></i> Class Management</a>
    <a href="teacher_dashboard.php?view=grade_management" class="nav-link sidebar-link <?= (isset($_GET['view']) && $_GET['view'] == 'grade_management') ? 'active' : '' ?>"><i class="fas fa-graduation-cap"></i> Grade Management</a>
    <a href="teacher_dashboard.php?view=performance_monitoring" class="nav-link sidebar-link <?= (isset($_GET['view']) && $_GET['view'] == 'performance_monitoring') ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Performance Monitoring</a>
    <a href="teacher_dashboard.php?view=predictive_analytics" class="nav-link sidebar-link <?= (isset($_GET['view']) && $_GET['view'] == 'predictive_analytics') ? 'active' : '' ?>"><i class="fas fa-brain"></i> Predictive Analytics</a>
    <a href="teacher_dashboard.php?view=feedback" class="nav-link sidebar-link <?= (isset($_GET['view']) && $_GET['view'] == 'feedback') ? 'active' : '' ?>"><i class="fas fa-comments"></i> Feedback</a>
    <a href="teacher_dashboard.php?view=reporting" class="nav-link sidebar-link <?= (isset($_GET['view']) && $_GET['view'] == 'reporting') ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Reporting</a>
</div>
