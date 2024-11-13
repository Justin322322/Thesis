<?php
// C:\xampp\htdocs\AcadMeter\public\teacher_dashboard.php

// Include the centralized initialization file
require_once '../config/init.php';

// Redirect to login if not authenticated as Instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: login.html');
    exit;
}

include('../config/db_connection.php');

// Function to fetch data securely
function fetchData($conn, $query, $param = null, $type = 'i') {
    $stmt = $conn->prepare($query);
    if ($param !== null) {
        $stmt->bind_param($type, $param);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    $stmt->close();
    return $data;
}

$user_id = $_SESSION['user_id'];
$instructorData = fetchData($conn, "SELECT instructor_id FROM instructors WHERE user_id = ?", $user_id);
$instructor_id = isset($instructorData[0]['instructor_id']) ? $instructorData[0]['instructor_id'] : 0;

// Fetch sections and students
$sections = fetchData($conn, "SELECT section_id, section_name FROM sections WHERE instructor_id = ?", $instructor_id);
$students = fetchData($conn, "SELECT s.student_id, u.first_name, u.last_name FROM students s JOIN users u ON s.user_id = u.user_id");

// Set default view to `dashboard_overview`
$allowed_views = ['dashboard_overview', 'class_management', 'grade_management', 'performance_monitoring', 'predictive_analytics', 'feedback', 'reporting'];
$view = isset($_GET['view']) && in_array($_GET['view'], $allowed_views) ? $_GET['view'] : 'dashboard_overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - AcadMeter</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/teacher_dashboard.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include('../includes/header.php'); ?>

        <!-- Determine which view to load -->
        <?php include("views/{$view}.php"); ?>
    </div>

    <!-- Include Footer -->
    <?php include('../includes/footer.php'); ?>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Bootstrap JS Bundle (includes Popper.js) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/teacher_dashboard.js"></script>
</body>
</html>
