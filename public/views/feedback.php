<?php
// public/views/feedback.php
?>
<div id="feedback" class="content-section">
    <h2>Feedback</h2>
    <!-- Add Feedback content here -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form>
                <div class="form-group">
                    <label for="feedbackStudent">Select Student</label>
                    <input type="search" id="feedbackStudentSearch" class="form-control" placeholder="Search student...">
                    <select id="feedbackStudent" name="student" class="form-control mt-2" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['student_id'] ?>"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="feedbackText">Feedback</label>
                    <textarea id="feedbackText" class="form-control" rows="4" placeholder="Enter your feedback here..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </form>
        </div>
    </div>
</div>
