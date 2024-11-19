console.log('Teacher Dashboard JS Initialized');

document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
});

function loadNotifications() {
    fetch('/AcadMeter/server/controllers/get_notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_notifications'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Error parsing JSON:', e);
            console.log('Raw response:', text);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.status === 'success') {
            updateNotificationsList(data.notifications);
        } else {
            console.error('Error loading notifications:', data.message);
            updateNotificationsList([{ message: 'Error loading notifications', created_at: new Date() }]);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        updateNotificationsList([{ message: 'Error: ' + error.message, created_at: new Date() }]);
    });
}

function updateNotificationsList(notifications) {
    const notificationItems = document.getElementById('notification-items');
    const notificationCount = document.getElementById('notification-count');
    
    if (!notificationItems) {
        console.warn('Notification items container not found. Make sure the element exists in your HTML.');
        return;
    }

    notificationItems.innerHTML = '';
    if (notifications.length === 0) {
        notificationItems.innerHTML = '<p class="dropdown-item">No new notifications</p>';
        notificationCount.textContent = '0';
    } else {
        notifications.forEach(notification => {
            const item = document.createElement('a');
            item.className = 'dropdown-item';
            item.href = '#';
            item.textContent = `${notification.message} - ${formatDate(notification.created_at)}`;
            notificationItems.appendChild(item);
        });
        notificationCount.textContent = notifications.length;
    }
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}