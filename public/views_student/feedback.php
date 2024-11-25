<?php
// feedback.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is a Student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Student') {
    header('Location: /AcadMeter/public/login.html');
    exit;
}

// Include database connection with corrected path
require_once __DIR__ . '/../../config/db_connection.php';

// Get student_id from user_id
$userId = $_SESSION['user_id'];
$studentQuery = "SELECT student_id FROM students WHERE user_id = ?";
$stmt = $conn->prepare($studentQuery);
if (!$stmt) {
    die('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$studentId = $student['student_id'] ?? 0;
$stmt->close();

// Fetch feedback with instructor names
$feedbackQuery = "
    SELECT 
        f.feedback_id,
        f.feedback_text,
        f.created_at,
        f.is_read,
        CONCAT(u.first_name, ' ', u.last_name) AS instructor_name
    FROM feedback f
    JOIN instructors i ON f.instructor_id = i.instructor_id
    JOIN users u ON i.user_id = u.user_id
    WHERE f.student_id = ?
    ORDER BY f.created_at DESC
";

$stmt = $conn->prepare($feedbackQuery);
if (!$stmt) {
    die('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
}
$stmt->bind_param("i", $studentId);
$stmt->execute();
$feedbackResult = $stmt->get_result();
$feedbackHistory = $feedbackResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Optionally, mark all as read after fetching feedback
$markReadQuery = "UPDATE feedback SET is_read = 1 WHERE student_id = ? AND is_read = 0";
$markReadStmt = $conn->prepare($markReadQuery);
if ($markReadStmt) {
    $markReadStmt->bind_param("i", $studentId);
    $markReadStmt->execute();
    $markReadStmt->close();
} else {
    // Handle prepare failure if necessary
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Head Elements -->
    <meta charset="UTF-8">
    <title>My Feedback</title>
    <!-- Bootstrap CSS -->

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.x/css/all.min.css">
    <style>

    </style>
</head>
<body>
    <div id="feedback" class="container mt-5">
        <h2 class="mb-4">My Feedback</h2>
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (!empty($feedbackHistory)): ?>
                    <?php foreach ($feedbackHistory as $feedback): ?>
                        <div class="feedback-item mb-4 p-3 border rounded <?= !$feedback['is_read'] ? 'unread' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1">From: <?= htmlspecialchars($feedback['instructor_name']) ?></h5>
                                    <small class="text-muted">
                                        <?= date('F j, Y g:i A', strtotime($feedback['created_at'])) ?>
                                    </small>
                                </div>
                                <?php if (!$feedback['is_read']): ?>
                                    <span class="badge badge-new">New</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-3">
                                <?= nl2br(htmlspecialchars($feedback['feedback_text'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        No feedback received yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.x/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Optional JavaScript: Checking for New Feedback Without Altering CSS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check for new feedback every minute and prompt user to reload
            setInterval(async () => {
                try {
                    const response = await fetch('feedback.php?action=check_new');
                    const data = await response.json();
                    if (data.hasNew) {
                        alert('You have new feedback. Please reload the page to view it.');
                        // Optionally, you can automatically reload:
                        // location.reload();
                    }
                } catch (error) {
                    console.error('Error checking for new feedback:', error);
                }
            }, 60000); // 60000ms = 1 minute
        });
    </script>
</body>
</html>