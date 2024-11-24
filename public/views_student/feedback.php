<?php
// feedback.php

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /login.php');
    exit;
}

// Include database connection
require_once __DIR__ . '/../../../config/db_connection.php';

// Fetch students from the database
$stmt = $conn->prepare("SELECT student_id, first_name, last_name FROM students ORDER BY last_name, first_name");
if (!$stmt) {
    die('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($student = $result->fetch_assoc()) {
    $students[] = $student;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include your head elements here -->
    <meta charset="UTF-8">
    <title>Feedback Module</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.x/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.x/css/all.min.css">
</head>
<body>
    <div id="feedback" class="container mt-5">
        <h2 class="mb-4">Feedback <i class="fas fa-info-circle text-primary" data-bs-toggle="tooltip" title="Provide feedback to students."></i></h2>
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="feedbackForm">
                    <!-- Student Selection -->
                    <div class="mb-3">
                        <label for="feedbackStudent" class="form-label">Select Student</label>
                        <div class="input-group">
                            <input type="search" id="feedbackStudentSearch" class="form-control" placeholder="Search student...">
                            <select id="feedbackStudent" name="student" class="form-select" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['student_id'] ?>"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <!-- Feedback Textarea -->
                    <div class="mb-3">
                        <label for="feedbackText" class="form-label">Feedback</label>
                        <textarea id="feedbackText" class="form-control" rows="4" placeholder="Enter your feedback here..." required></textarea>
                    </div>
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.x/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Feedback JS -->
    <script src="/public/assets/js/feedback.js"></script>
</body>
</html>
