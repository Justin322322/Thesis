<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\class_management_controller.php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary configurations and functions
require_once '../../config/db_connection.php';
require_once 'class_management_functions.php'; // Create this file for handling class management functions

header('Content-Type: application/json');

// Retrieve the action from the request
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'assign_students':
        assignStudents($conn);
        break;
    case 'assign_subject':
        assignSubject($conn);
        break;
    case 'add_subject':
        addSubject($conn);
        break;
    case 'edit_subject':
        editSubject($conn);
        break;
    case 'delete_subject':
        deleteSubject($conn);
        break;
    case 'fetch_subjects':
        fetchSubjects($conn);
        break;
    case 'fetch_sections':
        fetchSections($conn);
        break;
    case 'create_section':
        createSection($conn);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        break;
}

// Close the database connection
$conn->close();

// Define the functions in a separate file: class_management_functions.php
?>
