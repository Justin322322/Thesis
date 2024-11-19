<?php
// File: C:\xampp\htdocs\AcadMeter\server\models\Student.php

class Student {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($studentName, $email, $studentNumber) {
        $query = "INSERT INTO students (student_name, email, student_number) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sss", $studentName, $email, $studentNumber);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function update($studentId, $studentName, $email, $studentNumber) {
        $query = "UPDATE students SET student_name = ?, email = ?, student_number = ? WHERE student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssi", $studentName, $email, $studentNumber, $studentId);
        return $stmt->execute();
    }

    public function delete($studentId) {
        // First, remove all section associations
        $query1 = "DELETE FROM section_students WHERE student_id = ?";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->bind_param("i", $studentId);
        $stmt1->execute();

        // Then delete the student
        $query2 = "DELETE FROM students WHERE student_id = ?";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->bind_param("i", $studentId);
        return $stmt2->execute();
    }

    public function getAll() {
        $query = "SELECT * FROM students ORDER BY student_name";
        $result = $this->conn->query($query);
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        return $students;
    }

    public function getById($studentId) {
        $query = "SELECT * FROM students WHERE student_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getBySection($sectionId) {
        $query = "SELECT s.* FROM students s
                 JOIN section_students ss ON s.student_id = ss.student_id
                 WHERE ss.section_id = ?
                 ORDER BY s.student_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        return $students;
    }

    public function searchByName($searchTerm) {
        $searchTerm = "%$searchTerm%";
        $query = "SELECT * FROM students WHERE student_name LIKE ? ORDER BY student_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        return $students;
    }
}