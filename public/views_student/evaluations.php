<?php
// Start the session if it hasn't been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Student') {
    echo "You must be logged in as a student to view this page.";
    exit;
}

if (!defined('IN_STUDENT_DASHBOARD')) {
    define('IN_STUDENT_DASHBOARD', true);
    require_once __DIR__ . '/../../server/controllers/student_dashboard_controller.php';
    $studentDashboardController = new StudentDashboardController($conn);
}

$studentId = $_SESSION['user_id'];
// Fetch instructors for evaluation
$instructors = $studentDashboardController->getInstructorsForEvaluation($studentId);
?>

<div class="evaluation-view">
    <h2>Teacher Evaluation</h2>
    <?php if (!empty($instructors)): ?>
        <form id="evaluationForm" action="/AcadMeter/server/controllers/submit_evaluation.php" method="POST">
            <div class="form-group">
                <label for="instructorSelect">Select Instructor:</label>
                <select class="form-control" id="instructorSelect" name="instructor_id" required>
                    <option value="">Choose an instructor...</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?php echo htmlspecialchars($instructor['instructor_id']); ?>">
                            <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="evaluationText">Your Feedback:</label>
                <textarea class="form-control" id="evaluationText" name="evaluation" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Evaluation</button>
        </form>

        <script>
        document.getElementById('evaluationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Evaluation submitted successfully!');
                    this.reset();
                } else {
                    alert('Error submitting evaluation: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the evaluation.');
            });
        });
        </script>
    <?php else: ?>
        <p>No instructors are currently available for evaluation. Please check back later or contact your administrator if you believe this is an error.</p>
    <?php endif; ?>
</div>

