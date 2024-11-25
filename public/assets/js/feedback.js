// public/assets/js/feedback.js

class FeedbackManager {
    static async submitFeedback(studentId, feedbackText) {
        try {
            const response = await fetch('/AcadMeter/server/controllers/submit_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: studentId,
                    feedback: feedbackText,
                }),
            });

            if (!response.ok) {
                throw new Error(`Server responded with status ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                Toast.show('Success', 'Feedback submitted successfully', 'success');
                return true;
            } else {
                throw new Error(data.message || 'Failed to submit feedback');
            }
        } catch (error) {
            console.error('Feedback submission error:', error);
            Toast.show('Error', error.message, 'error');
            return false;
        }
    }

    static validateFeedback(text) {
        if (!text.trim()) {
            throw new Error('Feedback cannot be empty');
        }
        if (text.length > 1000) {
            throw new Error('Feedback must be less than 1000 characters');
        }
        return true;
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    const feedbackForm = document.getElementById('feedbackForm');
    const studentSelect = document.getElementById('studentSelect');
    
    if (feedbackForm && studentSelect) {
        feedbackForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const studentId = studentSelect.value;
            const feedbackText = document.getElementById('feedbackText').value;
            
            try {
                FeedbackManager.validateFeedback(feedbackText);
                const success = await FeedbackManager.submitFeedback(studentId, feedbackText);
                
                if (success) {
                    feedbackForm.reset();
                }
            } catch (error) {
                console.error('Validation/Submissions error:', error);
                Toast.show('Error', error.message, 'error');
            }
        });
    }
});