// feedback.js

document.addEventListener('DOMContentLoaded', function() {
    const feedbackForm = document.getElementById('feedbackForm');
    const feedbackStudentSearch = document.getElementById('feedbackStudentSearch');
    const feedbackStudent = document.getElementById('feedbackStudent');
    const feedbackText = document.getElementById('feedbackText');

    // Initialize Bootstrap tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Student Search Functionality
    feedbackStudentSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let hasVisibleOptions = false;

        Array.from(feedbackStudent.options).forEach(option => {
            const studentName = option.text.toLowerCase();
            if (studentName.includes(searchTerm) || option.value === "") {
                option.style.display = '';
                hasVisibleOptions = true;
            } else {
                option.style.display = 'none';
            }
        });

        if (!hasVisibleOptions) {
            feedbackStudent.selectedIndex = 0;
        }
    });

    // Form Submission Handling
    feedbackForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const selectedStudent = feedbackStudent.value;
        const feedbackTextValue = feedbackText.value.trim();

        // Data validation
        if (!selectedStudent) {
            alert('Please select a student.');
            return;
        }

        if (!feedbackTextValue) {
            alert('Please enter your feedback.');
            return;
        }

        // Disable the submit button to prevent multiple submissions
        const submitButton = feedbackForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        // AJAX request to server
        fetch('/AcadMeter/server/controllers/submit_feedback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                student_id: selectedStudent,
                feedback: feedbackTextValue
            })
        })
        .then(response => response.json())
        .then(data => {
            submitButton.disabled = false; // Re-enable the submit button
            if (data.success) {
                // Display success message
                const successMessage = document.createElement('div');
                successMessage.className = 'alert alert-success mt-3';
                successMessage.innerText = 'Feedback submitted successfully.';
                feedbackForm.appendChild(successMessage);

                // Remove the message after 5 seconds
                setTimeout(() => {
                    successMessage.remove();
                }, 5000);

                feedbackForm.reset();
            } else {
                alert('An error occurred: ' + data.error);
            }
        })
        .catch(error => {
            submitButton.disabled = false; // Re-enable the submit button
            console.error('Error:', error);
            alert('An unexpected error occurred. Please try again later.');
        });
    });
});
