// public/assets/js/general.js
$(document).ready(function () {
    // Sidebar highlight based on active view
    const currentView = new URLSearchParams(window.location.search).get('view') || 'dashboard_overview';
    $('.sidebar-link').removeClass('active');
    $(`.sidebar-link[href*="view=${currentView}"]`).addClass('active');

    // Notification dropdown toggle
    $('#notificationIcon').click(function (e) {
        e.preventDefault();
        $('#notification-dropdown').toggle();
    });

    // Close dropdown on outside click
    $(document).click(function (event) {
        if (!$(event.target).closest('#notificationIcon, #notification-dropdown').length) {
            $('#notification-dropdown').hide();
        }
    });
});
