<?php
// server/controllers/submit_feedback.php

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../services/FeedbackService.php';

session_start();

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch the corresponding instructor_id from the instructors table
$stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $instructorId = (int) $row['instructor_id'];
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Instructor record not found']);
    exit;
}

$feedbackService = new FeedbackService($conn, $instructorId);

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$studentId = isset($data['student_id']) ? intval($data['student_id']) : 0;
$feedbackText = isset($data['feedback']) ? trim($data['feedback']) : '';

$result = $feedbackService->submitFeedback($studentId, $feedbackText);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $result['error']]);
}

?>