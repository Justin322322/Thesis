<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\class_management_controller.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../models/Section.php';
require_once __DIR__ . '/../models/Subject.php';
require_once __DIR__ . '/../models/Student.php';

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_section':
            addSection($conn);
            break;
        case 'assign_student':
            assignStudent($conn);
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
        case 'remove_student':
            removeStudent($conn);
            break;
        case 'remove_subject':
            removeSubject($conn);
            break;
        case 'get_sections':
            getSections($conn);
            break;
        case 'get_subjects':
            getSubjects($conn);
            break;
        case 'get_students':
            getStudents($conn);
            break;
        case 'search_students':
            searchStudents($conn);
            break;
        case 'delete_section':
            deleteSection($conn);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
}

function addSection($conn) {
    $sectionName = $_POST['section_name'] ?? '';
    $userId = $_SESSION['user_id'] ?? 0;
    $schoolYear = $_POST['school_year'] ?? date('Y') . '-' . (date('Y') + 1);
    $subjectId = $_POST['subject_id'] ?? null;

    if (empty($sectionName) || empty($userId)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        return;
    }

    try {
        // First, get the instructor_id from the instructors table
        $stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Instructor not found']);
            return;
        }
        $instructor = $result->fetch_assoc();
        $instructorId = $instructor['instructor_id'];

        // Now insert the new section
        $stmt = $conn->prepare("INSERT INTO sections (section_name, subject_id, instructor_id, school_year) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $sectionName, $subjectId, $instructorId, $schoolYear);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Section added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add section: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function assignStudent($conn) {
    $studentId = $_POST['student_id'] ?? '';
    $sectionId = $_POST['section_id'] ?? '';

    if (empty($studentId) || empty($sectionId)) {
        echo json_encode(['status' => 'error', 'message' => 'Student and section are required']);
        return;
    }

    try {
        // Check if the student is already assigned to the section
        $stmt = $conn->prepare("SELECT * FROM section_students WHERE section_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $sectionId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Student is already assigned to this section']);
            return;
        }

        $stmt = $conn->prepare("INSERT INTO section_students (section_id, student_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $sectionId, $studentId);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Student assigned successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to assign student: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function assignSubject($conn) {
    $subjectId = $_POST['subject_id'] ?? '';
    $sectionId = $_POST['section_id'] ?? '';

    if (empty($subjectId) || empty($sectionId)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject and section are required']);
        return;
    }

    try {
        // Check if the subject is already assigned to the section
        $stmt = $conn->prepare("SELECT * FROM section_subjects WHERE section_id = ? AND subject_id = ?");
        $stmt->bind_param("ii", $sectionId, $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Subject is already assigned to this section']);
            return;
        }

        $stmt = $conn->prepare("INSERT INTO section_subjects (section_id, subject_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $sectionId, $subjectId);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Subject assigned successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to assign subject: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function addSubject($conn) {
    $subjectName = $_POST['subject_name'] ?? '';

    if (empty($subjectName)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject name is required']);
        return;
    }

    try {
        // Check if subject already exists
        $stmt = $conn->prepare("SELECT * FROM subjects WHERE subject_name = ?");
        $stmt->bind_param("s", $subjectName);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Subject already exists']);
            return;
        }

        $stmt = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        $stmt->bind_param("s", $subjectName);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Subject added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add subject: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function editSubject($conn) {
    $subjectId = $_POST['subject_id'] ?? '';
    $subjectName = $_POST['subject_name'] ?? '';

    if (empty($subjectId) || empty($subjectName)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject ID and name are required']);
        return;
    }

    try {
        // Check if the new subject name already exists
        $stmt = $conn->prepare("SELECT * FROM subjects WHERE subject_name = ? AND subject_id != ?");
        $stmt->bind_param("si", $subjectName, $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Another subject with this name already exists']);
            return;
        }

        $stmt = $conn->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
        $stmt->bind_param("si", $subjectName, $subjectId);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Subject updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update subject: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function deleteSubject($conn) {
    $subjectId = $_POST['subject_id'] ?? '';

    if (empty($subjectId)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject ID is required']);
        return;
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Delete related records in section_subjects
        $stmt = $conn->prepare("DELETE FROM section_subjects WHERE subject_id = ?");
        $stmt->bind_param("i", $subjectId);
        $stmt->execute();
        error_log("Deleted section_subjects for subject ID: " . $subjectId);

        // Delete the subject
        $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $stmt->bind_param("i", $subjectId);
        
        if ($stmt->execute()) {
            $conn->commit();
            error_log("Successfully deleted subject with ID: " . $subjectId);
            echo json_encode(['status' => 'success', 'message' => 'Subject deleted successfully']);
        } else {
            throw new Exception('Failed to delete subject: ' . $stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting subject: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete subject: ' . $e->getMessage()]);
    }
}

function removeStudent($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $studentId = $_POST['student_id'] ?? '';

    if (empty($sectionId) || empty($studentId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and student ID are required']);
        return;
    }

    try {
        // Check if the student is assigned to the section
        $stmt = $conn->prepare("SELECT * FROM section_students WHERE section_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $sectionId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Student is not assigned to this section']);
            return;
        }

        $stmt = $conn->prepare("DELETE FROM section_students WHERE section_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $sectionId, $studentId);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Student removed successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove student: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function removeSubject($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $subjectId = $_POST['subject_id'] ?? '';

    if (empty($sectionId) || empty($subjectId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and subject ID are required']);
        return;
    }

    try {
        // Check if the subject is assigned to the section
        $stmt = $conn->prepare("SELECT * FROM section_subjects WHERE section_id = ? AND subject_id = ?");
        $stmt->bind_param("ii", $sectionId, $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Subject is not assigned to this section']);
            return;
        }

        $stmt = $conn->prepare("DELETE FROM section_subjects WHERE section_id = ? AND subject_id = ?");
        $stmt->bind_param("ii", $sectionId, $subjectId);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Subject removed successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove subject: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getSections($conn) {
    try {
        $instructorId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            SELECT s.section_id, s.section_name, s.school_year, sub.subject_name
            FROM sections s
            LEFT JOIN subjects sub ON s.subject_id = sub.subject_id
            JOIN instructors i ON s.instructor_id = i.instructor_id
            WHERE i.user_id = ?
        ");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $sections = $result->fetch_all(MYSQLI_ASSOC);

        $classRoster = getClassRoster($conn, $instructorId);

        echo json_encode([
            'status' => 'success',
            'sections' => $sections,
            'classRoster' => $classRoster
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getClassRoster($conn, $instructorId) {
    $classRoster = [];
    try {
        $stmt = $conn->prepare("
            SELECT s.section_id, s.section_name, 
                   st.student_id, CONCAT(st.first_name, ' ', st.last_name) AS student_name,
                   sub.subject_id, sub.subject_name
            FROM sections s
            LEFT JOIN section_students ss ON s.section_id = ss.section_id
            LEFT JOIN students st ON ss.student_id = st.student_id
            LEFT JOIN section_subjects ssub ON s.section_id = ssub.section_id
            LEFT JOIN subjects sub ON ssub.subject_id = sub.subject_id
            JOIN instructors i ON s.instructor_id = i.instructor_id
            WHERE i.user_id = ?
        ");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $sectionId = $row['section_id'];
            $sectionName = $row['section_name'];
            
            if (!isset($classRoster[$sectionName])) {
                $classRoster[$sectionName] = [
                    'section_id' => $sectionId,
                    'students' => [],
                    'subjects' => []
                ];
            }
            
            if ($row['student_id'] && !in_array(['id' => $row['student_id'], 'name' => $row['student_name']], $classRoster[$sectionName]['students'])) {
                $classRoster[$sectionName]['students'][] = ['id' => $row['student_id'], 'name' => $row['student_name']];
            }
            
            if ($row['subject_id'] && !in_array(['id' => $row['subject_id'], 'name' => $row['subject_name']], $classRoster[$sectionName]['subjects'])) {
                $classRoster[$sectionName]['subjects'][] = ['id' => $row['subject_id'], 'name' => $row['subject_name']];
            }
        }
    } catch (Exception $e) {
        error_log("Error in getClassRoster: " . $e->getMessage());
    }
    return $classRoster;
}

function getSubjects($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM subjects");
        $stmt->execute();
        $result = $stmt->get_result();
        $subjects = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'status' => 'success',
            'subjects' => $subjects
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getStudents($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM students");
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'status' => 'success',
            'students' => $students
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function searchStudents($conn) {
    $searchTerm = $_POST['search_term'] ?? '';

    if (empty($searchTerm)) {
        echo json_encode(['status' => 'error', 'message' => 'Search term is required']);
        return;
    }

    try {
        $searchTerm = "%" . $searchTerm . "%";
        $stmt = $conn->prepare("SELECT * FROM students WHERE first_name LIKE ? OR last_name LIKE ?");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'status' => 'success',
            'students' => $students
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function deleteSection($conn) {
    $sectionId = $_POST['section_id'] ?? '';

    if (empty($sectionId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
        return;
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Log the deletion attempt
        error_log("Attempting to delete section with ID: " . $sectionId);

        // Delete related records in section_students
        $stmt = $conn->prepare("DELETE FROM section_students WHERE section_id = ?");
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        error_log("Deleted section_students for section ID: " . $sectionId);

        // Delete related records in section_subjects
        $stmt = $conn->prepare("DELETE FROM section_subjects WHERE section_id = ?");
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        error_log("Deleted section_subjects for section ID: " . $sectionId);

        // Delete the section
        $stmt = $conn->prepare("DELETE FROM sections WHERE section_id = ?");
        $stmt->bind_param("i", $sectionId);
        
        if ($stmt->execute()) {
            $conn->commit();
            error_log("Successfully deleted section with ID: " . $sectionId);
            echo json_encode(['status' => 'success', 'message' => 'Section deleted successfully']);
        } else {
            throw new Exception('Failed to delete section: ' . $stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting section: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete section: ' . $e->getMessage()]);
    }
}
?>
