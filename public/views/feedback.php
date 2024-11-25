<?php
// feedback.php

$current_page = 'feedback';

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.php');
    exit;
}

// Get the instructor_id from the session
$instructor_id = $_SESSION['user_id'];

// Fetch list of students taught by the instructor
$stmt = $conn->prepare("
    SELECT s.student_id, s.first_name, s.last_name
    FROM students s
    INNER JOIN section_students ss ON s.student_id = ss.student_id
    INNER JOIN sections sec ON ss.section_id = sec.section_id
    WHERE sec.instructor_id = ?
    GROUP BY s.student_id
");
if (!$stmt) {
    die('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
}
$stmt->bind_param('i', $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'student_id' => $row['student_id'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name']
    ];
}
$stmt->close();

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Optional: Your custom CSS -->
    <link rel="stylesheet" href="/AcadMeter/public/assets/css/styles.css">
</head>
<body>
    <!-- Include your navigation bar here -->

    <div class="container mt-4">
        <div id="feedback" class="content-section">
            <h2 class="mb-4">Feedback <i class="fas fa-info-circle text-primary" data-bs-toggle="tooltip" title="Provide feedback to students."></i></h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="feedbackForm">
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
                        <div class="mb-3">
                            <label for="feedbackText" class="form-label">Feedback</label>
                            <textarea id="feedbackText" name="feedback" class="form-control" rows="4" placeholder="Enter your feedback here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (for tooltips) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS (for icons) -->
    <!-- Already included in the head section -->
    <!-- Include jQuery (optional, if needed) -->
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <!-- Feedback JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        const feedbackForm = document.getElementById('feedbackForm');
        const feedbackStudentSearch = document.getElementById('feedbackStudentSearch');
        const feedbackStudent = document.getElementById('feedbackStudent');

        feedbackStudentSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            Array.from(feedbackStudent.options).forEach(option => {
                const studentName = option.text.toLowerCase();
                option.style.display = studentName.includes(searchTerm) ? '' : 'none';
            });
        });

        feedbackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const selectedStudentId = feedbackStudent.value;
            const selectedStudentName = feedbackStudent.options[feedbackStudent.selectedIndex].text;
            const feedbackText = document.getElementById('feedbackText').value.trim();

            if (!selectedStudentId) {
                alert('Please select a student.');
                return;
            }

            if (!feedbackText) {
                alert('Please enter your feedback.');
                return;
            }

            // Send data to the server using fetch API
            fetch('/AcadMeter/server/controllers/submit_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: selectedStudentId,
                    feedback: feedbackText,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Feedback submitted for ${selectedStudentName}.`);
                    feedbackForm.reset();
                } else {
                    alert(data.message || 'An error occurred while submitting feedback. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error submitting feedback:', error);
                alert('An error occurred while submitting feedback. Please try again.');
            });
        });
    });
    </script>
</body>
</html>