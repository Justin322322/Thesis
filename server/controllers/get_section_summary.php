<?php
// get_section_summary.php

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
    // Fetch grade distribution and student names across all sections taught by the instructor
    $stmt = $conn->prepare("
        SELECT
            CASE
                WHEN g.grade >= 90 THEN 'Outstanding'
                WHEN g.grade >= 80 THEN 'Very Satisfactory'
                WHEN g.grade >= 70 THEN 'Satisfactory'
                WHEN g.grade >= 60 THEN 'Fair'
                ELSE 'Needs Improvement'
            END AS grade_category,
            GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') AS student_names,
            COUNT(*) AS count
        FROM grades g
        JOIN students s ON g.student_id = s.student_id
        JOIN sections sec ON g.section_id = sec.section_id
        WHERE sec.instructor_id = ?
        GROUP BY grade_category
    ");
    if (!$stmt) {
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

    // Fetch grade distribution and student names for the specific section
    $stmt = $conn->prepare("
        SELECT
            CASE
                WHEN grade >= 90 THEN 'Outstanding'
                WHEN grade >= 80 THEN 'Very Satisfactory'
                WHEN grade >= 70 THEN 'Satisfactory'
                WHEN grade >= 60 THEN 'Fair'
                ELSE 'Needs Improvement'
            END AS grade_category,
            GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') AS student_names,
            COUNT(*) AS count
        FROM grades g
        JOIN students s ON g.student_id = s.student_id
        WHERE g.section_id = ?
        GROUP BY grade_category
    ");
    if (!$stmt) {
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('i', $section_id);
}

$stmt->execute();
$result = $stmt->get_result();

$sectionSummaryData = [];
while ($row = $result->fetch_assoc()) {
    $sectionSummaryData[] = [
        'grade_category' => $row['grade_category'],
        'student_names' => $row['student_names'],
        'count' => (int)$row['count']
    ];
}
$stmt->close();
$conn->close();

echo json_encode($sectionSummaryData);
?>
