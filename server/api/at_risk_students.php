<?php
// File: C:\xampp\htdocs\AcadMeter\server\api\at_risk_students.php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary configurations and functions
require_once '../../config/db_connection.php';
require_once '../controllers/teacher_dashboard_controller.php'; // Ensure this controller exists

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Optionally, implement authentication checks here
// e.g., check if the user is logged in and has the necessary permissions

// Fetch at-risk students using a function from the controller
$at_risk_students = getAtRiskStudents($conn); // Ensure this function exists in teacher_dashboard_controller.php

if ($at_risk_students !== false) {
    echo json_encode(['status' => 'success', 'students' => $at_risk_students]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve at-risk students.']);
}
?>
