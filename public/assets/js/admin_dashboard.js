document.addEventListener('DOMContentLoaded', function() {
    // Navigation logic
    const navLinks = document.querySelectorAll('.nav-link');
    const contentSections = document.querySelectorAll('.content-section');
    const sectionTitle = document.getElementById('section-title');

    navLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();

            // Remove 'active' class from all links and add to clicked one
            navLinks.forEach(link => link.classList.remove('active'));
            this.classList.add('active');

            // Hide all content sections and show target section
            contentSections.forEach(section => section.style.display = 'none');
            const targetSection = document.getElementById(this.dataset.section);
            targetSection.style.display = 'block';
            sectionTitle.textContent = `${this.textContent.trim()} - Admin Dashboard`;

            if (this.dataset.section === 'dashboard-overview') loadDashboardStats();
            if (this.dataset.section === 'user-management') loadPendingUsers();
        });
    });

    function loadDashboardStats() {
        fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=overview')
            .then(response => response.json())
            .then(data => {
                document.querySelector('.card.bg-primary .card-text').textContent = data.total_users;
                document.querySelector('.card.bg-danger .card-text').textContent = data.pending_approvals;
                document.querySelector('.card.bg-success .card-text').textContent = data.audit_logs;
                document.querySelector('.card.bg-info .card-text').textContent = data.reports_generated;
            })
            .catch(error => console.error('Error fetching dashboard stats:', error));
    }

    function loadPendingUsers() {
        fetch('/AcadMeter/server/controllers/admin_dashboard_function.php?action=pending_users')
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#user-management tbody');
                tbody.innerHTML = '';
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5">No pending users.</td></tr>';
                } else {
                    data.forEach(user => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${user.name}</td>
                                <td>${user.user_type}</td>
                                <td>${user.email}</td>
                                <td>${user.status}</td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="updateUserStatus(${user.user_id}, 'approve')">Approve</button>
                                    <button class="btn btn-danger btn-sm" onclick="updateUserStatus(${user.user_id}, 'reject')">Reject</button>
                                </td>
                            </tr>`;
                    });
                }
            })
            .catch(error => console.error('Error loading pending users:', error));
    }

    // Function to update user status
    window.updateUserStatus = function(userId, action) {
        fetch('/AcadMeter/server/controllers/admin_dashboard_function.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ userId: userId, action: action })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                loadPendingUsers();
            } else {
                console.error(data.message);
                alert(data.message);
            }
        })
        .catch(error => console.error('Error updating user status:', error));
    };
});
