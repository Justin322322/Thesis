<?php
// File: C:\xampp\htdocs\AcadMeter\server\models\Section.php

class Section {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($sectionName, $instructorId, $schoolYear) {
        $query = "INSERT INTO sections (section_name, instructor_id, school_year) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sis", $sectionName, $instructorId, $schoolYear);
        return $stmt->execute();
    }

    public function assignSubject($sectionId, $subjectId) {
        $query = "INSERT INTO section_subjects (section_id, subject_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $sectionId, $subjectId);
        return $stmt->execute();
    }

    public function removeSubject($sectionId, $subjectId) {
        $query = "DELETE FROM section_subjects WHERE section_id = ? AND subject_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $sectionId, $subjectId);
        return $stmt->execute();
    }

    public function assignStudent($sectionId, $studentId) {
        $query = "INSERT INTO section_students (section_id, student_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $sectionId, $studentId);
        return $stmt->execute();
    }

    public function removeStudent($sectionId, $studentId) {
        $query = "DELETE FROM section_students WHERE section_id = ? AND student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $sectionId, $studentId);
        return $stmt->execute();
    }

    public function getAllByInstructor($instructorId) {
        $query = "SELECT s.section_id, s.section_name, s.school_year, 
                         GROUP_CONCAT(DISTINCT sub.subject_name) as subjects,
                         COUNT(DISTINCT ss.student_id) as student_count
                  FROM sections s
                  LEFT JOIN section_subjects ssub ON s.section_id = ssub.section_id
                  LEFT JOIN subjects sub ON ssub.subject_id = sub.subject_id
                  LEFT JOIN section_students ss ON s.section_id = ss.section_id
                  WHERE s.instructor_id = ?
                  GROUP BY s.section_id, s.section_name, s.school_year";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sections = array();
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
        
        return $sections;
    }

    public function getClassRoster($instructorId) {
        $query = "SELECT s.section_id, s.section_name, s.school_year,
                         GROUP_CONCAT(DISTINCT sub.subject_name) as subjects,
                         GROUP_CONCAT(DISTINCT CONCAT(st.first_name, ' ', st.last_name)) as students
                  FROM sections s
                  LEFT JOIN section_subjects ssub ON s.section_id = ssub.section_id
                  LEFT JOIN subjects sub ON ssub.subject_id = sub.subject_id
                  LEFT JOIN section_students ss ON s.section_id = ss.section_id
                  LEFT JOIN students st ON ss.student_id = st.student_id
                  WHERE s.instructor_id = ?
                  GROUP BY s.section_id, s.section_name, s.school_year";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();

        $classRoster = array();
        while ($row = $result->fetch_assoc()) {
            $classRoster[$row['section_name']] = array(
                'school_year' => $row['school_year'],
                'subjects' => $row['subjects'] ? explode(',', $row['subjects']) : array(),
                'students' => $row['students'] ? explode(',', $row['students']) : array()
            );
        }

        return $classRoster;
    }
}