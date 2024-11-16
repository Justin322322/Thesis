<div id="feedback" class="content-section">
    <h2 class="mb-4">Feedback <i class="fas fa-info-circle text-primary" data-toggle="tooltip" title="Provide feedback to students."></i></h2>
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
                    <textarea id="feedbackText" class="form-control" rows="4" placeholder="Enter your feedback here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
        const selectedStudent = feedbackStudent.options[feedbackStudent.selectedIndex].text;
        const feedbackText = document.getElementById('feedbackText').value;
        alert(`Feedback submitted for ${selectedStudent}: ${feedbackText}`);
        // Here you would typically send this data to the server
    });
});
</script>