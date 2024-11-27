<?php
// File: server/controllers/class_management_controller.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Section.php';
require_once __DIR__ . '/../models/Subject.php';

session_start();

// Check if the user is logged in and is an Instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_section':
        addSection($conn);
        break;
    case 'get_sections':
        getSections($conn);
        break;
    case 'delete_section':
        deleteSection($conn);
        break;
    case 'add_subject':
        addSubject($conn);
        break;
    case 'get_subjects':
        getSubjects($conn);
        break;
    case 'edit_subject':
        editSubject($conn);
        break;
    case 'delete_subject':
        deleteSubject($conn);
        break;
    case 'assign_subject':
        assignSubject($conn);
        break;
    case 'remove_subject':
        removeSubject($conn);
        break;
    case 'get_students':
        getStudents($conn);
        break;
    case 'assign_student':
        assignStudent($conn);
        break;
    case 'remove_student':
        removeStudent($conn);
        break;
    case 'get_class_roster':
        $classRoster = getClassRoster($conn);
        if ($classRoster !== false) {
            echo json_encode(['status' => 'success', 'sections' => $classRoster]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch class roster']);
        }
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

function addSection($conn) {
    $sectionName = $_POST['section_name'] ?? '';
    $schoolYear = $_POST['school_year'] ?? '';
    $instructorId = $_SESSION['user_id'];

    if (empty($sectionName) || empty($schoolYear)) {
        echo json_encode(['status' => 'error', 'message' => 'Section name and school year are required']);
        return;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO sections (section_name, school_year, instructor_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $sectionName, $schoolYear, $instructorId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Section added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add section. It may already exist.']);
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo json_encode(['status' => 'error', 'message' => 'This section already exists. Please use a different name or school year.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while adding the section. Please try again.']);
        }
    }
}

function getSections($conn) {
    $instructorId = $_SESSION['user_id'];

    try {
        $stmt = $conn->prepare("SELECT s.section_id, s.section_name, s.school_year, 
                                COUNT(DISTINCT ss.student_id) as student_count,
                                GROUP_CONCAT(DISTINCT sub.subject_name SEPARATOR ', ') as subjects
                                FROM sections s
                                LEFT JOIN section_students ss ON s.section_id = ss.section_id
                                LEFT JOIN section_subjects ssub ON s.section_id = ssub.section_id
                                LEFT JOIN subjects sub ON ssub.subject_id = sub.subject_id
                                WHERE s.instructor_id = ?
                                GROUP BY s.section_id
                                ORDER BY s.section_name");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();

        $sections = [];
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }

        echo json_encode(['status' => 'success', 'sections' => $sections]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while fetching sections. Please try again.']);
    }
}

function deleteSection($conn) {
    $sectionId = $_POST['section_id'] ?? '';

    if (empty($sectionId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
        return;
    }

    try {
        $conn->begin_transaction();

        // Delete associated records in section_students and section_subjects, and the section itself
        $stmt = $conn->prepare("DELETE s, ss, ssub FROM sections s
                                LEFT JOIN section_students ss ON s.section_id = ss.section_id
                                LEFT JOIN section_subjects ssub ON s.section_id = ssub.section_id
                                WHERE s.section_id = ? AND s.instructor_id = ?");
        $stmt->bind_param("ii", $sectionId, $_SESSION['user_id']);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Section deleted successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete section. It may not exist or you may not have permission.']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while deleting the section. Please try again.']);
    }
}

function addSubject($conn) {
    $subjectName = $_POST['subject_name'] ?? '';

    if (empty($subjectName)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject name is required']);
        return;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        $stmt->bind_param("s", $subjectName);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Subject added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add subject. It may already exist.']);
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo json_encode(['status' => 'error', 'message' => 'This subject already exists. Please use a different name.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while adding the subject. Please try again.']);
        }
    }
}

function getSubjects($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM subjects ORDER BY subject_name");
        $stmt->execute();
        $result = $stmt->get_result();

        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }

        echo json_encode(['status' => 'success', 'subjects' => $subjects]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while fetching subjects. Please try again.']);
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
        $stmt = $conn->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
        $stmt->bind_param("si", $subjectName, $subjectId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Subject updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No changes were made to the subject']);
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo json_encode(['status' => 'error', 'message' => 'This subject name already exists. Please use a different name.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while updating the subject. Please try again.']);
        }
    }
}

function deleteSubject($conn) {
    $subjectId = $_POST['subject_id'] ?? '';

    if (empty($subjectId)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject ID is required']);
        return;
    }

    try {
        $conn->begin_transaction();

        // Delete associated records in section_subjects
        $stmt = $conn->prepare("DELETE FROM section_subjects WHERE subject_id = ?");
        $stmt->bind_param("i", $subjectId);
        $stmt->execute();

        // Delete the subject
        $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $stmt->bind_param("i", $subjectId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Subject deleted successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete subject. It may not exist.']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while deleting the subject. Please try again.']);
    }
}

function assignSubject($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $subjectId = $_POST['subject_id'] ?? '';

    if (empty($sectionId) || empty($subjectId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and Subject ID are required']);
        return;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO section_subjects (section_id, subject_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $sectionId, $subjectId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Subject assigned successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to assign subject. It may already be assigned to this section.']);
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo json_encode(['status' => 'error', 'message' => 'This subject is already assigned to the section.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while assigning the subject. Please try again.']);
        }
    }
}

function removeSubject($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $subjectId = $_POST['subject_id'] ?? '';

    if (empty($sectionId) || empty($subjectId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and Subject ID are required']);
        return;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM section_subjects WHERE section_id = ? AND subject_id = ?");
        $stmt->bind_param("ii", $sectionId, $subjectId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Subject removed successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove subject. It may not be assigned to this section.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while removing the subject. Please try again.']);
    }
}

function getStudents($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM students ORDER BY last_name, first_name");
        $stmt->execute();
        $result = $stmt->get_result();

        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        echo json_encode(['status' => 'success', 'students' => $students]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while fetching students. Please try again.']);
    }
}

function assignStudent($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $studentId = $_POST['student_id'] ?? '';

    if (empty($sectionId) || empty($studentId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and Student ID are required']);
        return;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO section_students (section_id, student_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $sectionId, $studentId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Student assigned successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to assign student. They may already be assigned to this section.']);
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo json_encode(['status' => 'error', 'message' => 'This student is already assigned to the section.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while assigning the student. Please try again.']);
        }
    }
}

function removeStudent($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $studentId = $_POST['student_id'] ?? '';

    if (empty($sectionId) || empty($studentId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and Student ID are required']);
        return;
    }

    try {
        error_log("Attempting to remove student: section_id=$sectionId, student_id=$studentId");
        $conn->begin_transaction();

        // Delete the student from the section
        $stmt = $conn->prepare("DELETE FROM section_students WHERE section_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $sectionId, $studentId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->commit();
            error_log("Student removed successfully: section_id=$sectionId, student_id=$studentId");
            echo json_encode(['status' => 'success', 'message' => 'Student removed successfully']);
        } else {
            $conn->rollback();
            error_log("Failed to remove student: section_id=$sectionId, student_id=$studentId");
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove student. They may not be assigned to this section.']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error removing student: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while removing the student. Please try again.']);
    }
}

function getClassRoster($conn) {
    $sql = "SELECT s.section_id, s.section_name, s.school_year, 
                   sub.subject_name, sub.subject_id, 
                   st.first_name, st.last_name, st.student_id
            FROM sections s
            LEFT JOIN section_subjects ss ON s.section_id = ss.section_id
            LEFT JOIN subjects sub ON ss.subject_id = sub.subject_id
            LEFT JOIN section_students sst ON s.section_id = sst.section_id
            LEFT JOIN students st ON sst.student_id = st.student_id
            WHERE s.instructor_id = ?
            ORDER BY s.section_name, sub.subject_name, st.last_name, st.first_name";
    
    $sections = [];

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $sectionId = $row['section_id'];
            if (!isset($sections[$sectionId])) {
                $sections[$sectionId] = [
                    'section_id' => $sectionId,
                    'section_name' => $row['section_name'],
                    'school_year' => $row['school_year'],
                    'subjects' => [],
                    'students' => []
                ];
            }
            if ($row['subject_id'] && !in_array(['id' => $row['subject_id'], 'name' => $row['subject_name']], $sections[$sectionId]['subjects'])) {
                $sections[$sectionId]['subjects'][] = ['id' => $row['subject_id'], 'name' => $row['subject_name']];
            }
            if ($row['student_id'] && !in_array(['id' => $row['student_id'], 'name' => $row['first_name'] . ' ' . $row['last_name']], $sections[$sectionId]['students'])) {
                $sections[$sectionId]['students'][] = ['id' => $row['student_id'], 'name' => $row['first_name'] . ' ' . $row['last_name']];
            }
        }
    } catch (Exception $e) {
        error_log("Error in getClassRoster: " . $e->getMessage());
        return false;
    }

    return array_values($sections);
}