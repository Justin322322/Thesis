<?php
// submit_feedback.php

header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Get the instructor_id from the session
$instructor_id = $_SESSION['user_id'];

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

$student_id = isset($data['student_id']) ? intval($data['student_id']) : 0;
$feedback_text = isset($data['feedback']) ? trim($data['feedback']) : '';

// Validate input
if ($student_id <= 0 || empty($feedback_text)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

// Verify that the student is taught by the instructor
$stmt = $conn->prepare("
    SELECT s.student_id
    FROM students s
    INNER JOIN section_students ss ON s.student_id = ss.student_id
    INNER JOIN sections sec ON ss.section_id = sec.section_id
    WHERE sec.instructor_id = ? AND s.student_id = ?
    GROUP BY s.student_id
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}
$stmt->bind_param('ii', $instructor_id, $student_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'You are not authorized to provide feedback to this student.']);
    exit;
}
$stmt->close();

// Insert feedback into the database
$stmt = $conn->prepare("
    INSERT INTO feedback (student_id, instructor_id, feedback_message)
    VALUES (?, ?, ?)
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}
$stmt->bind_param('iis', $student_id, $instructor_id, $feedback_text);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback.']);
}
$stmt->close();
$conn->close();
?>
