<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\notifications_controller.php

// Prevent direct access
if (!isset($_GET['action'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No action specified.']);
    exit;
}

require_once '../../config/init.php';
require_once '../../config/db_connection.php';

// Fetch Notifications Action
if ($_GET['action'] === 'fetch_notifications') {
    // Ensure the user is authenticated
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Example: Fetch notifications for the instructor
    $query = "SELECT notification_id, message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database prepare failed.']);
        exit;
    }

    $stmt->bind_param('i', $user_id);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database execute failed.']);
        exit;
    }

    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'notification_id' => $row['notification_id'],
            'message' => $row['message'],
            'link' => $row['link'],
            'created_at' => $row['created_at']
        ];
    }

    // Optionally, mark notifications as read
    // $updateQuery = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    // $updateStmt = $conn->prepare($updateQuery);
    // $updateStmt->bind_param('i', $user_id);
    // $updateStmt->execute();
    // $updateStmt->close();

    echo json_encode(['status' => 'success', 'notifications' => $notifications]);
    $stmt->close();
    exit;
}

// Handle other actions if necessary
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
?>
