// File: C:\xampp\htdocs\AcadMeter\public\assets\js\teacher_dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    console.log('Teacher Dashboard JS Initialized');

    // Toggle Sidebar
    const toggleButton = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (toggleButton && sidebar) {
        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    }

    // Handle Notifications
    loadNotifications();

    function loadNotifications() {
        fetch('/AcadMeter/server/controllers/get_notifications.php', {
            method: 'GET',
            credentials: 'include' // Include cookies for session
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateNotifications(data.notifications);
            } else {
                console.error('Failed to load notifications:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
    }

    function updateNotifications(notifications) {
        const notificationCount = document.getElementById('notification-count');
        const notificationItems = document.getElementById('notification-items');

        // Clear existing notifications
        notificationItems.innerHTML = '';

        if (notifications.length > 0) {
            notificationCount.textContent = notifications.length;
            notifications.forEach(notification => {
                const notificationElement = document.createElement('a');
                notificationElement.href = notification.link || '#';
                notificationElement.className = 'dropdown-item';
                notificationElement.innerHTML = `
                    <strong>${notification.message}</strong><br>
                    <small class="text-muted">${notification.created_at}</small>
                `;
                notificationItems.appendChild(notificationElement);
            });
        } else {
            notificationCount.textContent = '0';
            const noNotif = document.createElement('p');
            noNotif.className = 'dropdown-item';
            noNotif.textContent = 'No new notifications.';
            notificationItems.appendChild(noNotif);
        }
    }

    // Periodically refresh notifications every 5 minutes
    setInterval(loadNotifications, 300000); // 300,000 ms = 5 minutes

    // Toast notification system
    window.showToast = function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            console.error('Toast container not found');
            return;
        }

        const toastId = 'toast-' + Date.now();
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            this.remove();
        });
    };
});