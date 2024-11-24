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

// Fetch grade distribution for the section
$stmt = $conn->prepare("
    SELECT
        CASE
            WHEN grade >= 90 THEN 'A'
            WHEN grade >= 80 THEN 'B'
            WHEN grade >= 70 THEN 'C'
            WHEN grade >= 60 THEN 'D'
            ELSE 'F'
        END AS grade_category,
        COUNT(*) AS count
    FROM grades
    WHERE section_id = ?
    GROUP BY grade_category
");
if (!$stmt) {
    echo json_encode([]);
    exit;
}
$stmt->bind_param('i', $section_id);
$stmt->execute();
$result = $stmt->get_result();

$sectionSummaryData = [];
while ($row = $result->fetch_assoc()) {
    $sectionSummaryData[$row['grade_category']] = (int)$row['count'];
}
$stmt->close();
$conn->close();

echo json_encode($sectionSummaryData);
?>
