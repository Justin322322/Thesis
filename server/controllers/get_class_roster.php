<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\get_class_roster.php

session_start();
require_once '../config/database.php';

// Check if the user is logged in and has the necessary permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

function getClassRoster($conn) {
    $sql = "SELECT s.section_name, sub.subject_name, st.first_name, st.last_name 
            FROM sections s
            LEFT JOIN section_subjects ss ON s.section_id = ss.section_id
            LEFT JOIN subjects sub ON ss.subject_id = sub.subject_id
            LEFT JOIN section_students sst ON s.section_id = sst.section_id
            LEFT JOIN students st ON sst.student_id = st.student_id
            ORDER BY s.section_name, sub.subject_name, st.last_name, st.first_name";
    
    $roster = [];

    try {
        $result = $conn->query($sql);

        if ($result === false) {
            throw new Exception("Query failed: " . $conn->error);
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sectionName = $row['section_name'];
                if (!isset($roster[$sectionName])) {
                    $roster[$sectionName] = ['subjects' => [], 'students' => []];
                }
                if ($row['subject_name'] && !in_array($row['subject_name'], $roster[$sectionName]['subjects'])) {
                    $roster[$sectionName]['subjects'][] = $row['subject_name'];
                }
                if ($row['first_name'] && $row['last_name']) {
                    $studentName = $row['first_name'] . ' ' . $row['last_name'];
                    if (!in_array($studentName, $roster[$sectionName]['students'])) {
                        $roster[$sectionName]['students'][] = $studentName;
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error in getClassRoster: " . $e->getMessage());
        return false;
    }

    return $roster;
}

$classRoster = getClassRoster($conn);

header('Content-Type: application/json');
if ($classRoster === false) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while fetching the class roster']);
} else {
    echo json_encode(['status' => 'success', 'data' => $classRoster]);
}