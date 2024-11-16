document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const navLinks = document.querySelectorAll('.nav-link');
    const contentSections = document.querySelectorAll('.content-section');
    const sectionTitle = document.getElementById('section-title');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const userDropdown = document.getElementById('userDropdown');
    const logoutButton = document.getElementById('logoutButton');

    // Sidebar toggle
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });

    // Navigation
    navLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            navLinks.forEach(link => link.classList.remove('active'));
            this.classList.add('active');
            contentSections.forEach(section => section.style.display = 'none');
            const targetSection = document.getElementById(this.dataset.section);
            targetSection.style.display = 'block';
            sectionTitle.textContent = this.textContent.trim();

            if (this.dataset.section === 'dashboard-overview') {
                loadDashboardStats();
            } else if (this.dataset.section === 'user-management') {
                showTab('pending-approvals');
            }
        });
    });

    // Dropdowns
    function setupDropdown(dropdownElement) {
        const toggleButton = dropdownElement.querySelector('.dropdown-toggle');
        const menu = dropdownElement.querySelector('.dropdown-menu');

        toggleButton.addEventListener('click', function(event) {
            event.stopPropagation();
            menu.classList.toggle('show');
        });

        document.addEventListener('click', function(event) {
            if (!dropdownElement.contains(event.target)) {
                menu.classList.remove('show');
            }
        });
    }

    setupDropdown(notificationDropdown);
    setupDropdown(userDropdown);

    // Logout functionality
    logoutButton.addEventListener('click', function(event) {
        event.preventDefault();
        if (confirm('Are you sure you want to log out?')) {
            window.location.href = '/AcadMeter/server/controllers/logout.php';
        }
    });

    // Load dashboard stats
    function loadDashboardStats() {
        fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=overview')
            .then(response => response.json())
            .then(data => {
                if (data.status && data.status === 'error') {
                    showErrorMessage(data.message);
                    return;
                }
                document.getElementById('total-users').textContent = data.total_users || '0';
                document.getElementById('pending-approvals-count').textContent = data.pending_approvals || '0';
                document.getElementById('audit-logs').textContent = data.audit_logs || '0';
                document.getElementById('reports-generated').textContent = data.reports_generated || '0';
            })
            .catch(error => {
                console.error('Error fetching dashboard stats:', error);
                showErrorMessage('Failed to load dashboard statistics. Please try again later.');
            });
    }

    // Load pending users
    function loadPendingUsers() {
        fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=pending_users')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('pending-approvals-content');
                container.innerHTML = '';
                if (data.status && data.status === 'error') {
                    container.innerHTML = `<p class="text-danger">${data.message}</p>`;
                    return;
                }
                if (data.length === 0) {
                    container.innerHTML = '<p>No pending users.</p>';
                } else {
                    let tableHTML = `
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    data.forEach(user => {
                        tableHTML += `
                            <tr>
                                <td>${user.first_name} ${user.last_name}</td>
                                <td>${user.user_type}</td>
                                <td>${user.email}</td>
                                <td>${user.status}</td>
                                <td>
                                    <button class="btn btn-success btn-sm mr-2" onclick="updateUserStatus(${user.user_id}, 'approve')">Approve</button>
                                    <button class="btn btn-danger btn-sm" onclick="updateUserStatus(${user.user_id}, 'reject')">Reject</button>
                                </td>
                            </tr>`;
                    });
                    tableHTML += '</tbody></table>';
                    container.innerHTML = tableHTML;
                }
                loadNotifications();
            })
            .catch(error => {
                console.error('Error loading pending users:', error);
                showErrorMessage('Failed to load pending users. Please try again later.');
            });
    }

    // Load delete users
    function loadDeleteUsers() {
        fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=delete_users_list')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('delete-users-content');
                container.innerHTML = '';
                if (data.status && data.status === 'error') {
                    container.innerHTML = `<p class="text-danger">${data.message}</p>`;
                    return;
                }
                if (data.length === 0) {
                    container.innerHTML = '<p>No users available for deletion.</p>';
                } else {
                    let tableHTML = `
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    data.forEach(user => {
                        tableHTML += `
                            <tr>
                                <td>${user.first_name} ${user.last_name}</td>
                                <td>${user.user_type}</td>
                                <td>${user.email}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="confirmDelete(${user.user_id})">Delete</button>
                                </td>
                            </tr>`;
                    });
                    tableHTML += '</tbody></table>';
                    container.innerHTML = tableHTML;
                }
            })
            .catch(error => {
                console.error('Error loading delete users list:', error);
                showErrorMessage('Failed to load users for deletion. Please try again later.');
            });
    }

    // Function to update user status
    function updateUserStatus(userId, action) {
        console.log('updateUserStatus called with:', userId, action);
        if (confirm(`Are you sure you want to ${action} this user?`)) {
            console.log(`Updating user ID ${userId} with action ${action}`);
            fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=update_user_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ userId: userId, userAction: action })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Update user status response:', data);
                if (data.status === 'success') {
                    console.log('Calling showSuccessMessage with:', data.message);
                    showSuccessMessage(data.message);
                    loadPendingUsers();
                    loadDashboardStats();
                    loadNotifications();
                } else {
                    console.log('Calling showErrorMessage with:', data.message);
                    showErrorMessage(data.message || 'An error occurred while updating user status.');
                }
            })
            .catch(error => {
                console.error('Error updating user status:', error);
                showErrorMessage('Failed to update user status. Please try again later.');
            });
        }
    }

    // Function to confirm and delete user
    function confirmDelete(userId) {
        if (confirm("Are you sure you want to delete this user?")) {
            console.log(`Deleting user ID ${userId}`);
            fetch('/AcadMeter/server/controllers/admin_dashboard_function.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_user', userId: userId })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Delete user response:', data);
                if (data.status === 'success') {
                    showSuccessMessage(data.message);
                    loadDeleteUsers();
                    loadDashboardStats();
                } else {
                    showErrorMessage(data.message || 'An error occurred while deleting the user.');
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                showErrorMessage('Failed to delete user. Please try again later.');
            });
        }
    }

    // Load notifications
    function loadNotifications() {
        fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=get_notifications')
            .then(response => response.json())
            .then(data => {
                const notificationCount = document.getElementById('notification-count');
                const notificationItems = document.getElementById('notification-items');
                notificationItems.innerHTML = '';

                if (data.status && data.status === 'error') {
                    notificationItems.innerHTML = `<p class="text-danger">${data.message}</p>`;
                    return;
                }

                if (data.length > 0) {
                    notificationCount.style.display = 'inline-block';
                    notificationCount.textContent = data.length;

                    data.forEach(notification => {
                        notificationItems.innerHTML += `
                            <div class="dropdown-item">
                                <p>${notification.message}</p>
                                <small class="text-muted">${notification.timestamp}</small>
                            </div>`;
                    });
                } else {
                    notificationCount.style.display = 'none';
                    notificationItems.innerHTML = '<p class="dropdown-item">No new notifications.</p>';
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                showErrorMessage('Failed to load notifications. Please try again later.');
            });
    }

    // Tab functionality
    window.showTab = function(tabName) {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabPanes.forEach(pane => pane.style.display = 'none');

        document.getElementById(`${tabName}-btn`).classList.add('active');
        document.getElementById(tabName).style.display = 'block';

        if (tabName === 'pending-approvals') {
            loadPendingUsers();
        } else if (tabName === 'delete-users') {
            loadDeleteUsers();
        }
    };

    // Show alert function
    function showAlert(message, type) {
        const alertPlaceholder = document.getElementById('alertPlaceholder');
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type}`;
        const icon = type === 'success' ? '✅' : '❌';
        alertElement.innerHTML = `
            <div class="alert-content">
                <span class="alert-icon">${icon}</span>
                ${message}
            </div>
            <button type="button" class="close" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        alertPlaceholder.appendChild(alertElement);

        // Trigger reflow to enable animation
        alertElement.offsetHeight;
        alertElement.classList.add('show');

        const closeButton = alertElement.querySelector('.close');
        closeButton.addEventListener('click', () => {
            alertElement.classList.remove('show');
            alertElement.classList.add('hide');
            setTimeout(() => alertElement.remove(), 300);
        });

        // Auto-dismiss the alert after 5 seconds
        setTimeout(() => {
            alertElement.classList.remove('show');
            alertElement.classList.add('hide');
            setTimeout(() => alertElement.remove(), 300);
        }, 5000);
    }

    // Show success message
    function showSuccessMessage(message) {
        showAlert(message, 'success');
    }

    // Show error message
    function showErrorMessage(message) {
        showAlert(message, 'danger');
    }

    // Make functions globally accessible
    window.updateUserStatus = updateUserStatus;
    window.confirmDelete = confirmDelete;
    window.showSuccessMessage = showSuccessMessage;
    window.showErrorMessage = showErrorMessage;

    // Initial load
    loadDashboardStats();
    loadNotifications();

    // Refresh dashboard data every 5 minutes
    setInterval(() => {
        loadDashboardStats();
        loadNotifications();
    }, 300000);
});