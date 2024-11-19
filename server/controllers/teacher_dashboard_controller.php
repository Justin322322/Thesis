<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\teacher_dashboard_controller.php

// Prevent direct access
if (!defined('IN_TEACHER_DASHBOARD')) {
    die('Direct access not permitted');
}

// Include the database connection using absolute path
require_once __DIR__ . '/../../config/db_connection.php';

// Function to fetch data securely with enhanced error handling
function fetchData($conn, $query, $params = [], $types = '') {
    if (!$conn) {
        die("Database connection is not established.");
    }

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }

    if (!empty($params)) {
        // Ensure types string matches number of params
        if (strlen($types) !== count($params)) {
            die("Mismatch between types and parameters count.");
        }
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        die("Execute failed: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    if ($result === false) {
        die("Get result failed: " . htmlspecialchars($stmt->error));
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

$user_id = $_SESSION['user_id'];

// Fetch instructor_id
$instructorData = fetchData($conn, "SELECT instructor_id FROM instructors WHERE user_id = ?", [$user_id], 'i');
$instructor_id = isset($instructorData[0]['instructor_id']) ? $instructorData[0]['instructor_id'] : null;

if ($instructor_id === null) {
    // Instructor not found, handle accordingly
    header('Location: /AcadMeter/public/login.php');
    exit;
}

// Fetch sections for this instructor
$sections = fetchData($conn, "SELECT section_id, section_name FROM sections WHERE instructor_id = ?", [$instructor_id], 'i');

// Fetch all students
$students = fetchData($conn, "SELECT s.student_id, u.first_name, u.last_name FROM students s JOIN users u ON s.user_id = u.user_id");
?>