<?php
// File: C:\xampp\htdocs\AcadMeter\server\models\Subject.php

class Subject {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($subjectName) {
        $query = "INSERT INTO subjects (subject_name) VALUES (?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $subjectName);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function update($subjectId, $subjectName) {
        $query = "UPDATE subjects SET subject_name = ? WHERE subject_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $subjectName, $subjectId);
        return $stmt->execute();
    }

    public function delete($subjectId) {
        // First, remove all section associations
        $query1 = "DELETE FROM section_subjects WHERE subject_id = ?";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->bind_param("i", $subjectId);
        $stmt1->execute();

        // Then delete the subject
        $query2 = "DELETE FROM subjects WHERE subject_id = ?";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->bind_param("i", $subjectId);
        return $stmt2->execute();
    }

    public function getAll() {
        $query = "SELECT * FROM subjects ORDER BY subject_name";
        $result = $this->conn->query($query);
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        return $subjects;
    }

    public function getById($subjectId) {
        $query = "SELECT * FROM subjects WHERE subject_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getBySection($sectionId) {
        $query = "SELECT s.* FROM subjects s
                 JOIN section_subjects ss ON s.subject_id = ss.subject_id
                 WHERE ss.section_id = ?
                 ORDER BY s.subject_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        return $subjects;
    }
}