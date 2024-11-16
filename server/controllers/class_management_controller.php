<?php
// C:\xampp\htdocs\AcadMeter\server\controllers\class_management_controller.php

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Validate instructor session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    sendResponse('error', 'Unauthorized access');
    exit;
}

require_once '../../config/db_connection.php';

/**
 * Function to validate CSRF token
 */
function validate_csrf_token($csrf_token) {
    return isset($csrf_token, $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $csrf_token);
}

/**
 * Function to send JSON response
 */
function sendResponse($status, $message, $data = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
}

/**
 * Function to log errors
 */
function logError($message) {
    error_log($message . "\n", 3, __DIR__ . '/../../logs/error_log.txt');
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $data = $_POST;

    // Validate CSRF token
    if (!validate_csrf_token($data['csrf_token'] ?? '')) {
        sendResponse('error', 'Invalid CSRF token');
        exit;
    }

    // Determine the action
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'assign_students_to_section':
            assignStudentsToSection($conn, $data);
            break;

        case 'assign_subject_to_section':
            assignSubjectToSection($conn, $data);
            break;

        case 'add_subject':
            addSubject($conn, $data);
            break;

        case 'update_subject':
            updateSubject($conn, $data);
            break;

        case 'delete_subject':
            deleteSubject($conn, $data);
            break;

        default:
            sendResponse('error', 'Invalid action');
            break;
    }
} else {
    sendResponse('error', 'Invalid request method');
}

$conn->close();

/**
 * Assign Students to a Section
 *
 * @param mysqli $conn - Database connection
 * @param array $data - Input data
 */
function assignStudentsToSection($conn, $data) {
    $section_id = intval($data['section_id'] ?? 0);
    $students = $data['students'] ?? [];

    if ($section_id <= 0 || empty($students)) {
        sendResponse('error', 'Invalid section or students selection');
        return;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Prepare statements
        $stmt_check = $conn->prepare("SELECT 1 FROM section_students WHERE section_id = ? AND student_id = ?");
        $stmt_insert = $conn->prepare("INSERT INTO section_students (section_id, student_id) VALUES (?, ?)");

        if (!$stmt_check || !$stmt_insert) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $inserted = 0;
        $already_assigned = 0;

        foreach ($students as $student_id) {
            $student_id = intval($student_id);
            $stmt_check->bind_param("ii", $section_id, $student_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $already_assigned++;
            } else {
                $stmt_insert->bind_param("ii", $section_id, $student_id);
                if ($stmt_insert->execute()) {
                    $inserted++;
                } else {
                    throw new Exception('Failed to assign student ID ' . $student_id . ': ' . $stmt_insert->error);
                }
            }
        }

        $stmt_check->close();
        $stmt_insert->close();

        // Commit transaction
        $conn->commit();

        $message = "$inserted student(s) assigned successfully.";
        if ($already_assigned > 0) {
            $message .= " $already_assigned student(s) were already assigned to this section.";
        }

        sendResponse('success', $message);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        // Log the error
        logError("Error assigning students: " . $e->getMessage());
        sendResponse('error', 'Error assigning students: ' . $e->getMessage());
    }
}

/**
 * Assign Subject to a Section
 *
 * @param mysqli $conn - Database connection
 * @param array $data - Input data
 */
function assignSubjectToSection($conn, $data) {
    $section_id = intval($data['section_id'] ?? 0);
    $subject_id = intval($data['subject_id'] ?? 0);

    if ($section_id <= 0 || $subject_id <= 0) {
        sendResponse('error', 'Invalid section or subject selection');
        return;
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update the subject for the section
        $stmt_update = $conn->prepare("UPDATE sections SET subject_id = ? WHERE section_id = ?");
        if (!$stmt_update) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $stmt_update->bind_param("ii", $subject_id, $section_id);
        if (!$stmt_update->execute()) {
            throw new Exception('Failed to assign subject to section: ' . $stmt_update->error);
        }
        $stmt_update->close();

        // Commit transaction
        $conn->commit();

        sendResponse('success', 'Subject assigned to section successfully.');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        // Log the error
        logError("Error assigning subject: " . $e->getMessage());
        sendResponse('error', 'Error assigning subject: ' . $e->getMessage());
    }
}

/**
 * Add a New Subject
 *
 * @param mysqli $conn - Database connection
 * @param array $data - Input data
 */
function addSubject($conn, $data) {
    $subject_name = trim($data['subject_name'] ?? '');

    if (empty($subject_name)) {
        sendResponse('error', 'Subject name cannot be empty');
        return;
    }

    // Check if subject already exists (case-insensitive)
    $stmt_check = $conn->prepare("SELECT 1 FROM subjects WHERE LOWER(subject_name) = LOWER(?)");
    if (!$stmt_check) {
        // Log the error
        logError("Database error during subject existence check: " . $conn->error);
        sendResponse('error', 'Database error: ' . $conn->error);
        return;
    }
    $stmt_check->bind_param("s", $subject_name);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        sendResponse('error', 'Subject already exists');
        return;
    }
    $stmt_check->close();

    // Insert new subject
    $stmt_insert = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
    if (!$stmt_insert) {
        // Log the error
        logError("Database error during subject insertion: " . $conn->error);
        sendResponse('error', 'Database error: ' . $conn->error);
        return;
    }
    $stmt_insert->bind_param("s", $subject_name);

    if ($stmt_insert->execute()) {
        sendResponse('success', 'Subject added successfully', [
            'subject_id' => $stmt_insert->insert_id,
            'subject_name' => htmlspecialchars($subject_name)
        ]);
    } else {
        // Log the error
        logError("Database error during subject insertion: " . $stmt_insert->error);
        sendResponse('error', 'Failed to add subject: ' . $stmt_insert->error);
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
    $subject_name = trim($data['subject_name'] ?? '');

    if ($subject_id <= 0 || empty($subject_name)) {
        sendResponse('error', 'Invalid subject ID or subject name');
        return;
    }

    // Check if subject exists
    $stmt_check = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
    if (!$stmt_check) {
        logError("Database error during subject existence check: " . $conn->error);
        sendResponse('error', 'Database error: ' . $conn->error);
        return;
    }
    $stmt_check->bind_param("i", $subject_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        $stmt_check->close();
        sendResponse('error', 'Subject not found');
        return;
    }

    $existing_subject = $result->fetch_assoc()['subject_name'];
    $stmt_check->close();

    // Update subject name
    $stmt_update = $conn->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
    if (!$stmt_update) {
        logError("Database error during subject update: " . $conn->error);
        sendResponse('error', 'Database error: ' . $conn->error);
        return;
    }
    $stmt_update->bind_param("si", $subject_name, $subject_id);

    if ($stmt_update->execute()) {
        sendResponse('success', 'Subject updated successfully', [
            'subject_name' => htmlspecialchars($subject_name),
            'old_subject_name' => htmlspecialchars($existing_subject)
        ]);
    } else {
        logError("Database error during subject update: " . $stmt_update->error);
        sendResponse('error', 'Failed to update subject: ' . $stmt_update->error);
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
        sendResponse('error', 'Invalid subject ID');
        return;
    }

    // Check if subject exists and retrieve its name
    $stmt_check = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
    if (!$stmt_check) {
        logError("Database error during subject existence check: " . $conn->error);
        sendResponse('error', 'Database error: ' . $conn->error);
        return;
    }
    $stmt_check->bind_param("i", $subject_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        $stmt_check->close();
        sendResponse('error', 'Subject not found');
        return;
    }

    $subject_name = $result->fetch_assoc()['subject_name'];
    $stmt_check->close();

    // Check if the subject is assigned to any section
    $stmt_assigned = $conn->prepare("SELECT COUNT(*) as count FROM sections WHERE subject_id = ?");
    if (!$stmt_assigned) {
        logError("Database error during subject assignment check: " . $conn->error);
        sendResponse('error', 'Database error: ' . $conn->error);
        return;
    }
    $stmt_assigned->bind_param("i", $subject_id);
    $stmt_assigned->execute();
    $result_assigned = $stmt_assigned->get_result();
    $count = $result_assigned->fetch_assoc()['count'];
    $stmt_assigned->close();

    if ($count > 0) {
        sendResponse('error', 'Cannot delete subject. It is currently assigned to one or more sections.');
        return;
    }

    // Proceed to delete the subject
    $stmt_delete = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
    if (!$stmt_delete) {
        logError("Database error during subject deletion: " . $conn->error);
        sendResponse('error', 'Database error: ' . $conn->error);
        return;
    }
    $stmt_delete->bind_param("i", $subject_id);

    if ($stmt_delete->execute()) {
        sendResponse('success', 'Subject deleted successfully', [
            'deleted_subject_name' => htmlspecialchars($subject_name)
        ]);
    } else {
        logError("Database error during subject deletion: " . $stmt_delete->error);
        sendResponse('error', 'Failed to delete subject: ' . $stmt_delete->error);
    }

    $stmt_delete->close();
}
