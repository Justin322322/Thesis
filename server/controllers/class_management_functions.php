<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\class_management_functions.php

// Function to assign a student to a section
function assignStudents($conn) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }

    // Retrieve and sanitize input
    $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

    if ($section_id <= 0 || $student_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid section or student ID.']);
        exit;
    }

    // Assign student to section
    $query = "UPDATE students SET section_id = ? WHERE student_id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ii", $section_id, $student_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Student assigned to section successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to assign student: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}

// Function to assign a subject to a section
function assignSubject($conn) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }

    // Retrieve and sanitize input
    $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;

    if ($section_id <= 0 || $subject_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid section or subject ID.']);
        exit;
    }

    // Assign subject to section
    $query = "UPDATE sections SET subject_id = ? WHERE section_id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ii", $subject_id, $section_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Subject assigned to section successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to assign subject: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}

// Function to add a new subject
function addSubject($conn) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }

    // Retrieve and sanitize input
    $subject_name = isset($_POST['subject_name']) ? trim($_POST['subject_name']) : '';

    if (empty($subject_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject name cannot be empty.']);
        exit;
    }

    // Check if subject already exists
    $check_query = "SELECT subject_id FROM subjects WHERE subject_name = ?";
    if ($stmt = $conn->prepare($check_query)) {
        $stmt->bind_param("s", $subject_name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Subject already exists.']);
            $stmt->close();
            exit;
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    // Insert new subject
    $insert_query = "INSERT INTO subjects (subject_name) VALUES (?)";
    if ($stmt = $conn->prepare($insert_query)) {
        $stmt->bind_param("s", $subject_name);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Subject added successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add subject: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}

// Function to edit an existing subject
function editSubject($conn) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }

    // Retrieve and sanitize input
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    $subject_name = isset($_POST['subject_name']) ? trim($_POST['subject_name']) : '';

    if ($subject_id <= 0 || empty($subject_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid subject ID or name.']);
        exit;
    }

    // Update subject
    $update_query = "UPDATE subjects SET subject_name = ? WHERE subject_id = ?";
    if ($stmt = $conn->prepare($update_query)) {
        $stmt->bind_param("si", $subject_name, $subject_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Subject updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update subject: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}

// Function to delete a subject
function deleteSubject($conn) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }

    // Retrieve and sanitize input
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;

    if ($subject_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid subject ID.']);
        exit;
    }

    // Check if the subject is assigned to any section
    $check_query = "SELECT section_id FROM sections WHERE subject_id = ?";
    if ($stmt = $conn->prepare($check_query)) {
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete subject assigned to a section.']);
            $stmt->close();
            exit;
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    // Delete the subject
    $delete_query = "DELETE FROM subjects WHERE subject_id = ?";
    if ($stmt = $conn->prepare($delete_query)) {
        $stmt->bind_param("i", $subject_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Subject deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete subject: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}

// Function to fetch all subjects
function fetchSubjects($conn) {
    $query = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_id ASC";
    if ($stmt = $conn->prepare($query)) {
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $subjects = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode(['status' => 'success', 'subjects' => $subjects]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch subjects: ' . $stmt->error]);
            $stmt->close();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}

// Function to fetch all sections
function fetchSections($conn) {
    $query = "SELECT sections.section_id, sections.section_name, subjects.subject_name 
              FROM sections 
              LEFT JOIN subjects ON sections.subject_id = subjects.subject_id 
              ORDER BY sections.section_id ASC";
    if ($stmt = $conn->prepare($query)) {
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $sections = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode(['status' => 'success', 'sections' => $sections]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch sections: ' . $stmt->error]);
            $stmt->close();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}

// Function to create a new section
function createSection($conn) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }

    // Retrieve and sanitize input
    $section_name = isset($_POST['section_name']) ? trim($_POST['section_name']) : '';
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : null; // Optional

    if (empty($section_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Section name cannot be empty.']);
        exit;
    }

    // Insert new section
    if ($subject_id > 0) {
        $query = "INSERT INTO sections (section_name, subject_id, instructor_id) VALUES (?, ?, ?)";
    } else {
        $query = "INSERT INTO sections (section_name, instructor_id) VALUES (?, ?)";
    }

    // Assuming instructor_id is stored in the session
    $instructor_id = isset($_SESSION['instructor_id']) ? intval($_SESSION['instructor_id']) : 0;
    if ($instructor_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid instructor ID.']);
        exit;
    }

    if ($stmt = $conn->prepare($query)) {
        if ($subject_id > 0) {
            $stmt->bind_param("sii", $section_name, $subject_id, $instructor_id);
        } else {
            $stmt->bind_param("si", $section_name, $instructor_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Section created successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create section: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
