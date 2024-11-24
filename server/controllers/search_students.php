<?php
// search_students.php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

// Get the search term from the query string
$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';

$searchTerm = '%' . $searchTerm . '%';

// Prepare and execute the query
$stmt = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE CONCAT(first_name, ' ', last_name) LIKE ? ORDER BY last_name, first_name LIMIT 10");
$stmt->bind_param('s', $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($student = $result->fetch_assoc()) {
    $students[] = [
        'label' => $student['first_name'] . ' ' . $student['last_name'],
        'value' => $student['student_id']
    ];
}

$stmt->close();
$conn->close();

// Return the results as JSON
echo json_encode($students);
?>
