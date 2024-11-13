<?php
// save_student_data.php
session_start();
include('../config/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate user and get the necessary data from POST
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $current_grade = $_POST['current_grade'];
    $attendance = $_POST['attendance'];
    
    // Prepare the SQL query to save data to `grades` table (or other relevant tables)
    $stmt = $conn->prepare("INSERT INTO grades (student_id, subject_id, quarterly_grade) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $student_id, $subject_id, $current_grade);
    
    if ($stmt->execute()) {
        echo "Data saved successfully!";
    } else {
        echo "Error saving data.";
    }
    $stmt->close();
}
$conn->close();
?>
