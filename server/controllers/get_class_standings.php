<?php
// get_class_standings.php

// Enable error reporting for debugging (Remove or comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

if ($section_id === 0) {
    // Modified query to select first_name and last_name
    $stmt = $conn->prepare("
        SELECT DISTINCT
            s.student_id, 
            s.first_name,
            s.last_name,
            ROUND(AVG(g.grade), 2) AS average_grade,
            CASE
                WHEN AVG(g.grade) >= 90 THEN 'Outstanding'
                WHEN AVG(g.grade) >= 80 THEN 'Very Satisfactory'
                WHEN AVG(g.grade) >= 70 THEN 'Satisfactory'
                WHEN AVG(g.grade) >= 60 THEN 'Fair'
                ELSE 'Needs Improvement'
            END AS grade_category
        FROM students s
        JOIN grades g ON s.student_id = g.student_id
        JOIN sections sec ON g.section_id = sec.section_id
        WHERE sec.instructor_id = ?
        GROUP BY s.student_id
        ORDER BY average_grade DESC
    ");
    if (!$stmt) {
        // Log the error and return an empty array
        error_log('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('i', $instructor_id);
} else {
    // Verify that the section belongs to the instructor
    $stmt = $conn->prepare("
        SELECT section_id
        FROM sections
        WHERE section_id = ? AND instructor_id = ?
    ");
    if (!$stmt) {
        error_log('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
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

    // Fetch students and their average grades in the specific section
    $stmt = $conn->prepare("
        SELECT s.student_id, s.first_name, s.last_name, AVG(g.grade) AS average_grade,
            CASE
                WHEN AVG(g.grade) >= 90 THEN 'Outstanding'
                WHEN AVG(g.grade) >= 80 THEN 'Very Satisfactory'
                WHEN AVG(g.grade) >= 70 THEN 'Satisfactory'
                WHEN AVG(g.grade) >= 60 THEN 'Fair'
                ELSE 'Needs Improvement'
            END AS grade_category
        FROM grades g
        JOIN students s ON g.student_id = s.student_id
        WHERE g.section_id = ?
        GROUP BY s.student_id
        ORDER BY average_grade DESC
    ");
    if (!$stmt) {
        error_log('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('i', $section_id);
}

$stmt->execute();
$result = $stmt->get_result();

$classStandingsData = [];
while ($row = $result->fetch_assoc()) {
    $classStandingsData[] = [
        'student_id' => $row['student_id'],
        'student_name' => $row['first_name'] . ' ' . $row['last_name'],
        'average_grade' => round($row['average_grade'], 2),
        'grade_category' => $row['grade_category']
    ];
}
$stmt->close();
$conn->close();

echo json_encode($classStandingsData);
?>
