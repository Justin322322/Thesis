<?php
// generate_grade_report.php

// Start session
session_start();

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

// Get the instructor_id from the session
$instructor_id = $_SESSION['user_id'];

// Get the section_id from the GET parameter
if (!isset($_GET['section_id']) || empty($_GET['section_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Section ID is required.';
    exit;
}
$section_id = intval($_GET['section_id']);

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

// Verify that the section belongs to the instructor
$stmt = $conn->prepare("
    SELECT section_name
    FROM sections
    WHERE section_id = ? AND instructor_id = ?
");
if (!$stmt) {
    die('Database error.');
}
$stmt->bind_param('ii', $section_id, $instructor_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    header('HTTP/1.1 403 Forbidden');
    echo 'You are not authorized to access this section.';
    exit;
}
$stmt->bind_result($section_name);
$stmt->fetch();
$stmt->close();

// Fetch grades for the section
$stmt = $conn->prepare("
    SELECT s.student_id, s.first_name, s.last_name, g.subject_id, sub.subject_name, g.grade
    FROM grades g
    INNER JOIN students s ON g.student_id = s.student_id
    INNER JOIN subjects sub ON g.subject_id = sub.subject_id
    WHERE g.section_id = ?
    ORDER BY s.last_name, s.first_name, sub.subject_name
");
if (!$stmt) {
    die('Database error.');
}
$stmt->bind_param('i', $section_id);
$stmt->execute();
$result = $stmt->get_result();

// Prepare CSV file
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=Grade_Report_Section_{$section_name}.csv");

$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['Student ID', 'First Name', 'Last Name', 'Subject', 'Grade']);

// Write data to CSV
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['student_id'],
        $row['first_name'],
        $row['last_name'],
        $row['subject_name'],
        $row['grade']
    ]);
}

fclose($output);
$stmt->close();
$conn->close();
exit;
?>
