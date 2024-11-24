<?php
// get_performance_trend.php

header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode([]);
    exit;
}

// Get the instructor_id from the session
$instructor_id = $_SESSION['user_id'];

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

// Fetch data for Monthly Performance Trends
$stmt = $conn->prepare("
    SELECT MONTH(g.created_at) AS month, AVG(g.grade) AS average_score
    FROM grades g
    JOIN sections s ON g.section_id = s.section_id
    WHERE s.instructor_id = ?
    GROUP BY MONTH(g.created_at)
    ORDER BY MONTH(g.created_at)
");
if (!$stmt) {
    echo json_encode([]);
    exit;
}
$stmt->bind_param('i', $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

$performanceTrendData = [];
while ($row = $result->fetch_assoc()) {
    $monthNum = (int)$row['month'];
    $monthName = date('M', mktime(0, 0, 0, $monthNum, 10)); // Get month name
    $performanceTrendData[] = [
        'month' => $monthNum,
        'month_name' => $monthName,
        'average_score' => round($row['average_score'], 2)
    ];
}
$stmt->close();
$conn->close();

echo json_encode($performanceTrendData);
?>
