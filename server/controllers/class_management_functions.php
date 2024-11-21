<?php
// File: server/controllers/class_management_functions.php

function createSection($conn, $instructorId) {
    $sectionName = $_POST['section_name'] ?? '';
    $schoolYear = $_POST['school_year'] ?? '';

    if (empty($sectionName) || empty($schoolYear)) {
        echo json_encode(['status' => 'error', 'message' => 'Section name and school year are required.']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO sections (section_name, instructor_id, school_year) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $stmt->bind_param("sis", $sectionName, $instructorId, $schoolYear);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Section created successfully.']);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create section.']);
    }
    $stmt->close();
}

function assignStudents($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $studentIds = $_POST['student_ids'] ?? [];

    if (empty($sectionId) || empty($studentIds)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and student IDs are required.']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO section_students (section_id, student_id) VALUES (?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $conn->begin_transaction();
    try {
        foreach ($studentIds as $studentId) {
            $stmt->bind_param("ii", $sectionId, $studentId);
            $stmt->execute();
        }
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Students assigned successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error assigning students: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to assign students.']);
    }
    $stmt->close();
}

function assignSubject($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $subjectId = $_POST['subject_id'] ?? '';

    if (empty($sectionId) || empty($subjectId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and subject ID are required.']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO section_subjects (section_id, subject_id) VALUES (?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $stmt->bind_param("ii", $sectionId, $subjectId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Subject assigned successfully.']);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to assign subject.']);
    }
    $stmt->close();
}

function addSubject($conn) {
    $subjectName = $_POST['subject_name'] ?? '';

    if (empty($subjectName)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject name is required.']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $stmt->bind_param("s", $subjectName);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Subject added successfully.']);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to add subject.']);
    }
    $stmt->close();
}

function editSubject($conn) {
    $subjectId = $_POST['subject_id'] ?? '';
    $subjectName = $_POST['subject_name'] ?? '';

    if (empty($subjectId) || empty($subjectName)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject ID and name are required.']);
        return;
    }

    $stmt = $conn->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $stmt->bind_param("si", $subjectName, $subjectId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Subject updated successfully.']);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update subject.']);
    }
    $stmt->close();
}

function deleteSubject($conn) {
    $subjectId = $_POST['subject_id'] ?? '';

    if (empty($subjectId)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject ID is required.']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $stmt->bind_param("i", $subjectId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Subject deleted successfully.']);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete subject.']);
    }
    $stmt->close();
}

function fetchSubjects($conn) {
    $stmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $subjects = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'subjects' => $subjects]);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch subjects.']);
    }
    $stmt->close();
}

function fetchSections($conn, $instructorId) {
    $stmt = $conn->prepare("
        SELECT s.section_id, s.section_name, s.school_year,
               GROUP_CONCAT(DISTINCT sub.subject_name) AS subjects,
               COUNT(DISTINCT ss.student_id) AS student_count,
               GROUP_CONCAT(DISTINCT CONCAT(st.first_name, ' ', st.last_name, ':', st.student_id)) AS students
        FROM sections s
        LEFT JOIN section_subjects ssub ON s.section_id = ssub.section_id
        LEFT JOIN subjects sub ON ssub.subject_id = sub.subject_id
        LEFT JOIN section_students ss ON s.section_id = ss.section_id
        LEFT JOIN students st ON ss.student_id = st.student_id
        WHERE s.instructor_id = ?
        GROUP BY s.section_id
    ");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $stmt->bind_param("i", $instructorId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $sections = $result->fetch_all(MYSQLI_ASSOC);
        
        // Process the results
        foreach ($sections as &$section) {
            $section['subjects'] = $section['subjects'] ? explode(',', $section['subjects']) : [];
            $students = [];
            if ($section['students']) {
                foreach (explode(',', $section['students']) as $student) {
                    list($name, $id) = explode(':', $student);
                    $students[] = ['student_name' => $name, 'student_id' => $id];
                }
            }
            $section['students'] = $students;
        }
        
        echo json_encode(['status' => 'success', 'sections' => $sections]);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch sections.']);
    }
    $stmt->close();
}

function getClassRoster($conn) {
    $sectionId = $_POST['section_id'] ?? '';

    if (empty($sectionId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID is required.']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name
        FROM students s
        JOIN section_students ss ON s.student_id = ss.student_id
        WHERE ss.section_id = ?
    ");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $stmt->bind_param("i", $sectionId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'students' => $students]);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch class roster.']);
    }
    $stmt->close();
}

function removeSubjectFromSection($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $subjectId = $_POST['subject_id'] ?? '';

    if (empty($sectionId) || empty($subjectId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and subject ID are required.']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM section_subjects WHERE section_id = ? AND subject_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $stmt->bind_param("ii", $sectionId, $subjectId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Subject removed from section successfully.']);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove subject from section.']);
    }
    $stmt->close();
}

function removeStudentFromSection($conn) {
    $sectionId = $_POST['section_id'] ?? '';
    $studentId = $_POST['student_id'] ?? '';

    if (empty($sectionId) || empty($studentId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and student ID are required.']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM section_students WHERE section_id = ? AND student_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        return;
    }

    $stmt->bind_param("ii", $sectionId, $studentId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Student removed from section successfully.']);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove student from section.']);
    }
    $stmt->close();
}

function deleteSection($conn, $instructorId) {
    $sectionId = $_POST['section_id'] ?? '';

    if (empty($sectionId)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID is required.']);
        return;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete section_students entries
        $stmt = $conn->prepare("DELETE FROM section_students WHERE section_id = ?");
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $stmt->close();

        // Delete section_subjects entries
        $stmt = $conn->prepare("DELETE FROM section_subjects WHERE section_id = ?");
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $stmt->close();

        // Delete the section
        $stmt = $conn->prepare("DELETE FROM sections WHERE section_id = ? AND instructor_id = ?");
        $stmt->bind_param("ii", $sectionId, $instructorId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Section deleted successfully.']);
        } else {
            throw new Exception("No section found or you don't have permission to delete this section.");
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting section: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>