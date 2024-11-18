<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\assign_student.php

// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../config/db_connection.php';

// Validate CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
    exit;
}

// Validate and sanitize inputs
$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;

if ($student_id <= 0 || $section_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid student or section ID.']);
    exit;
}

// Check if the student exists
$stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404); // Not Found
    echo json_encode(['status' => 'error', 'message' => 'Student not found.']);
    exit;
}
$stmt->close();

// Check if the section exists
$stmt = $conn->prepare("SELECT section_id FROM sections WHERE section_id = ?");
$stmt->bind_param('i', $section_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404); // Not Found
    echo json_encode(['status' => 'error', 'message' => 'Section not found.']);
    exit;
}
$stmt->close();

// Assign the student to the section
$stmt = $conn->prepare("UPDATE students SET section_id = ? WHERE student_id = ?");
$stmt->bind_param('ii', $section_id, $student_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Student assigned to section successfully.']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Failed to assign student to section.']);
}

$stmt->close();
$conn->close();
?>
