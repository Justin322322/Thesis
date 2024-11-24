<?php
// get_class_standings.php

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

// Get the instructor_id and section_id from the request
$instructor_id = $_SESSION['user_id'];
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

// Verify that the section belongs to the instructor
$stmt = $conn->prepare("
    SELECT section_id
    FROM sections
    WHERE section_id = ? AND instructor_id = ?
");
if (!$stmt) {
    echo json_encode([]);
    exit;
}
$stmt->bind_param('ii', $section_id, $instructor_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    echo json_encode([]);
    exit;
}
$stmt->close();

// Fetch students and their average grades in the section
$stmt = $conn->prepare("
    SELECT s.student_id, s.first_name, s.last_name, AVG(g.grade) AS average_grade
    FROM grades g
    JOIN students s ON g.student_id = s.student_id
    WHERE g.section_id = ?
    GROUP BY s.student_id
    ORDER BY average_grade DESC
");
if (!$stmt) {
    echo json_encode([]);
    exit;
}
$stmt->bind_param('i', $section_id);
$stmt->execute();
$result = $stmt->get_result();

$classStandingsData = [];
while ($row = $result->fetch_assoc()) {
    $classStandingsData[] = [
        'student_id' => $row['student_id'],
        'student_name' => $row['first_name'] . ' ' . $row['last_name'],
        'average_grade' => round($row['average_grade'], 2)
    ];
}
$stmt->close();
$conn->close();

echo json_encode($classStandingsData);
?>
