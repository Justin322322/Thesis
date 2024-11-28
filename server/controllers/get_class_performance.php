<?php
// get_class_performance.php

header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode([]);
    exit;
}

// Get the instructor_id from the session
$instructor_id = $_SESSION['user_id'];

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

// Fetch average grades per section taught by the instructor
$stmt = $conn->prepare("
    SELECT 
        sec.section_name, 
        ROUND(AVG(g.grade), 2) AS average_score
    FROM grades g
    JOIN sections sec ON g.section_id = sec.section_id
    WHERE sec.instructor_id = ?
    GROUP BY sec.section_id, sec.section_name
    ORDER BY sec.section_name
");

if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param('i', $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

$classPerformanceData = [];
while ($row = $result->fetch_assoc()) {
    $classPerformanceData[] = [
        'section_name' => $row['section_name'],
        'average_score' => (float)$row['average_score']
    ];
}

$stmt->close();
$conn->close();

echo json_encode($classPerformanceData);
?>
