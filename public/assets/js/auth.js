document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form[action="../server/controllers/login_controller.php"]');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    const togglePassword = document.querySelector('.toggle-password');
    const forgotPasswordLink = document.getElementById('forgot-password');
    const modal = document.getElementById('forgot-password-modal');
    const closeModal = modal.querySelector('.close');
    const resetForm = document.getElementById('password-reset-form');
    const resetEmailInput = document.getElementById('reset-email');
    const resetMessage = document.getElementById('reset-message');

    function handleUrlParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        const email = urlParams.get('email');

        if (error) {
            errorMessage.textContent = decodeURIComponent(error);
            errorMessage.style.display = 'block';
        }

        if (email) {
            emailInput.value = decodeURIComponent(email);
        }

        // Remove parameters from URL
        if (error || email) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    handleUrlParameters();

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    // Login form submission
    loginForm.addEventListener('submit', function(e) {
        // Do not prevent default form submission
        // The PHP script will handle redirection
    });

    // Open forgot password modal
    forgotPasswordLink.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'block';
    });

    // Close modal
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Password reset form submission
    resetForm.addEventListener('submit', function(e) {
        e.preventDefault();
        resetMessage.style.display = 'none';
        resetMessage.className = 'reset-message'; // Reset classes
    
        const formData = new FormData(resetForm);
        fetch('../server/controllers/password_reset_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Server response:', data);
            resetMessage.textContent = data.message;
            if (data.status === 'success') {
                resetMessage.classList.add('success');
            } else {
                resetMessage.classList.add('error');
            }
            resetMessage.style.display = 'block';
        })
        .catch(error => {
            console.error('Password reset error:', error);
            resetMessage.textContent = 'An error occurred. Please try again.';
            resetMessage.classList.add('error');
            resetMessage.style.display = 'block';
        });
    });
});