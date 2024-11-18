<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\delete_subject.php

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

// Validate and sanitize input
$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;

if ($subject_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid subject ID.']);
    exit;
}

// Check if the subject exists
$stmt = $conn->prepare("SELECT subject_id FROM subjects WHERE subject_id = ?");
$stmt->bind_param('i', $subject_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404); // Not Found
    echo json_encode(['status' => 'error', 'message' => 'Subject not found.']);
    exit;
}
$stmt->close();

// Delete the subject
$stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
$stmt->bind_param('i', $subject_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Subject deleted successfully.']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete subject.']);
}

$stmt->close();
$conn->close();
?>
