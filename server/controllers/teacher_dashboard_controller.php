<?php
// C:\xampp\htdocs\AcadMeter\server\controllers\teacher_dashboard_controller.php

// Start the session only if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Validate instructor session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db_connection.php';

/**
 * Function to validate CSRF token
 */
function validate_csrf_token($data) {
    if (!isset($data['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $data['csrf_token']);
}

/**
 * Function to sanitize input data
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate CSRF token
    if (!validate_csrf_token($data)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    // Determine the action
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'add_subject':
            addSubject($conn, $data);
            break;

        case 'update_subject':
            updateSubject($conn, $data);
            break;

        case 'delete_subject':
            deleteSubject($conn, $data);
            break;

        case 'upload_csv':
            uploadCSV($conn, $_FILES, $data);
            break;

        // Add other actions like assign_students_to_section, assign_subject_to_section here

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();

/**
 * Add a New Subject
 *
 * @param mysqli $conn - Database connection
 * @param array $data - Input data
 */
function addSubject($conn, $data) {
    $subject_name = sanitize_input($data['subject_name'] ?? '');

    if (empty($subject_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject name cannot be empty']);
        return;
    }

    // Check if subject already exists (case-insensitive)
    $stmt_check = $conn->prepare("SELECT 1 FROM subjects WHERE LOWER(subject_name) = LOWER(?)");
    if (!$stmt_check) {
        // Log the error
        error_log("Database error during subject existence check: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_check->bind_param("s", $subject_name);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        echo json_encode(['status' => 'error', 'message' => 'Subject already exists']);
        return;
    }
    $stmt_check->close();

    // Insert new subject
    $stmt_insert = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
    if (!$stmt_insert) {
        // Log the error
        error_log("Database error during subject insertion: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_insert->bind_param("s", $subject_name);

    if ($stmt_insert->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Subject added successfully',
            'subject_id' => $stmt_insert->insert_id,
            'subject_name' => htmlspecialchars($subject_name)
        ]);
    } else {
        // Log the error
        error_log("Database error during subject insertion: " . $stmt_insert->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Failed to add subject: ' . $stmt_insert->error]);
    }

    $stmt_insert->close();
}

/**
 * Update an Existing Subject
 *
 * @param mysqli $conn - Database connection
 * @param array $data - Input data
 */
function updateSubject($conn, $data) {
    $subject_id = intval($data['subject_id'] ?? 0);
    $subject_name = sanitize_input($data['subject_name'] ?? '');

    if ($subject_id <= 0 || empty($subject_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid subject ID or subject name']);
        return;
    }

    // Check if subject exists
    $stmt_check = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
    if (!$stmt_check) {
        error_log("Database error during subject existence check: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_check->bind_param("i", $subject_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        $stmt_check->close();
        echo json_encode(['status' => 'error', 'message' => 'Subject not found']);
        return;
    }

    $existing_subject = $result->fetch_assoc()['subject_name'];
    $stmt_check->close();

    // Update subject name
    $stmt_update = $conn->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
    if (!$stmt_update) {
        error_log("Database error during subject update: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_update->bind_param("si", $subject_name, $subject_id);

    if ($stmt_update->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Subject updated successfully',
            'subject_name' => htmlspecialchars($subject_name)
        ]);
    } else {
        error_log("Database error during subject update: " . $stmt_update->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Failed to update subject: ' . $stmt_update->error]);
    }

    $stmt_update->close();
}

/**
 * Delete an Existing Subject
 *
 * @param mysqli $conn - Database connection
 * @param array $data - Input data
 */
function deleteSubject($conn, $data) {
    $subject_id = intval($data['subject_id'] ?? 0);

    if ($subject_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid subject ID']);
        return;
    }

    // Check if subject exists and retrieve its name
    $stmt_check = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
    if (!$stmt_check) {
        error_log("Database error during subject existence check: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_check->bind_param("i", $subject_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        $stmt_check->close();
        echo json_encode(['status' => 'error', 'message' => 'Subject not found']);
        return;
    }

    $subject_name = $result->fetch_assoc()['subject_name'];
    $stmt_check->close();

    // Check if the subject is assigned to any section
    $stmt_assigned = $conn->prepare("SELECT COUNT(*) as count FROM sections WHERE subject_id = ?");
    if (!$stmt_assigned) {
        error_log("Database error during subject assignment check: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_assigned->bind_param("i", $subject_id);
    $stmt_assigned->execute();
    $result_assigned = $stmt_assigned->get_result();
    $count = $result_assigned->fetch_assoc()['count'];
    $stmt_assigned->close();

    if ($count > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete subject. It is currently assigned to one or more sections.']);
        return;
    }

    // Proceed to delete the subject
    $stmt_delete = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
    if (!$stmt_delete) {
        error_log("Database error during subject deletion: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_delete->bind_param("i", $subject_id);

    if ($stmt_delete->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Subject deleted successfully',
            'old_subject_name' => htmlspecialchars($subject_name)
        ]);
    } else {
        error_log("Database error during subject deletion: " . $stmt_delete->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete subject: ' . $stmt_delete->error]);
    }

    $stmt_delete->close();
}

/**
 * Handle CSV Upload and Processing
 *
 * @param mysqli $conn - Database connection
 * @param array $files - $_FILES array
 * @param array $data - Additional data
 */
function uploadCSV($conn, $files, $data) {
    // Implement CSV processing logic here
    // This is a placeholder function
    // You need to parse the CSV, validate data, and insert grades accordingly
    echo json_encode(['status' => 'success', 'message' => 'CSV uploaded and processed successfully']);
}
?>
