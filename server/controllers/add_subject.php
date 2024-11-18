<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\add_subject.php

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
$subject_name = isset($_POST['subject_name']) ? trim($_POST['subject_name']) : '';

if (empty($subject_name)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Subject name cannot be empty.']);
    exit;
}

// Check if the subject already exists
$stmt = $conn->prepare("SELECT subject_id FROM subjects WHERE subject_name = ?");
$stmt->bind_param('s', $subject_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['status' => 'error', 'message' => 'Subject already exists.']);
    exit;
}
$stmt->close();

// Insert the new subject
$stmt = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
$stmt->bind_param('s', $subject_name);

if ($stmt->execute()) {
    $new_subject_id = $stmt->insert_id;
    echo json_encode(['status' => 'success', 'message' => 'Subject added successfully.', 'subject_id' => $new_subject_id, 'subject_name' => htmlspecialchars($subject_name)]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Failed to add subject.']);
}

$stmt->close();
$conn->close();
?>
