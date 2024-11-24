document.addEventListener('DOMContentLoaded', function() {
    // Function to switch tabs
    function switchTab(view) {
        // Update URL without reloading the page
        history.pushState(null, '', `?view=${view}`);
        
        // Fetch the new content
        fetch(`/AcadMeter/public/views_student/${view}.php`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('content-section').innerHTML = html;
                
                // Update active state in sidebar
                document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                document.querySelector(`.sidebar-nav .nav-link[href="?view=${view}"]`).classList.add('active');
                
                // Update section title
                const sectionTitle = document.getElementById('section-title');
                sectionTitle.textContent = view.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());

                // Initialize view-specific JavaScript
                if (view === 'performance') {
                    initializePerformanceView();
                }
            })
            .catch(error => {
                console.error('Error loading view:', error);
                showErrorMessage('Failed to load content. Please try again later.');
            });
    }

    // Add click event listeners to sidebar links
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const view = this.getAttribute('href').split('=')[1];
            switchTab(view);
        });
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        const view = new URLSearchParams(window.location.search).get('view') || 'dashboard_overview';
        switchTab(view);
    });

    // Toggle sidebar
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    });

    // Initialize notifications
    function loadNotifications() {
        fetch('/AcadMeter/server/controllers/student_dashboard_controller.php?action=get_notifications')
            .then(response => response.json())
            .then(data => {
                const notificationItems = document.getElementById('notification-items');
                const notificationCount = document.getElementById('notification-count');
                notificationItems.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(notification => {
                        const item = document.createElement('a');
                        item.className = 'dropdown-item';
                        item.href = '#';
                        item.innerHTML = `
                            <div>${notification.message}</div>
                            <small class="text-muted">${new Date(notification.created_at).toLocaleString()}</small>
                        `;
                        notificationItems.appendChild(item);
                    });
                    notificationCount.textContent = data.length;
                } else {
                    notificationItems.innerHTML = '<p class="dropdown-item">No new notifications.</p>';
                    notificationCount.textContent = '0';
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                showErrorMessage('Failed to load notifications. Please try again later.');
            });
    }

    // Load notifications on page load
    loadNotifications();

    // Logout functionality
    const logoutButton = document.getElementById('logoutButton');
    logoutButton.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to log out?')) {
            window.location.href = '/AcadMeter/server/controllers/logout.php';
        }
    });

    function initializePerformanceView() {
        // The chart initialization is now handled in the performance.php file
        console.log('Performance view initialized');
    }

    // Function to show error messages
    function showErrorMessage(message) {
        const alertPlaceholder = document.getElementById('alertPlaceholder');
        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            `<div class="alert alert-danger alert-dismissible" role="alert">`,
            `   <div>${message}</div>`,
            '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            '</div>'
        ].join('');
        alertPlaceholder.append(wrapper);
    }

    // Initial load
    const initialView = new URLSearchParams(window.location.search).get('view') || 'dashboard_overview';
    switchTab(initialView);

    // Refresh notifications every 5 minutes
    setInterval(loadNotifications, 300000);
});

