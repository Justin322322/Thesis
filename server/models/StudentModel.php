<?php
class StudentModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getStudentData($userId) {
        $query = "SELECT s.*, u.email, u.username, u.user_type, u.status, u.created_at, u.verified, u.dob, u.sex 
                  FROM students s 
                  JOIN users u ON s.user_id = u.user_id 
                  WHERE u.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getGradeSummary($userId) {
        $query = "SELECT s.subject_name, g.grade 
                  FROM grades g 
                  JOIN subjects s ON g.subject_id = s.subject_id 
                  JOIN students st ON g.student_id = st.student_id
                  JOIN users u ON st.user_id = u.user_id
                  WHERE u.user_id = ? 
                  ORDER BY g.created_at DESC 
                  LIMIT 5";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getDetailedGrades($userId) {
        $query = "SELECT s.subject_name, g.grade, g.quarter, g.academic_year, gc.component_name 
                  FROM grades g 
                  JOIN subjects s ON g.subject_id = s.subject_id 
                  JOIN grade_components gc ON g.component_id = gc.component_id 
                  JOIN students st ON g.student_id = st.student_id
                  JOIN users u ON st.user_id = u.user_id
                  WHERE u.user_id = ? 
                  ORDER BY g.academic_year DESC, g.quarter DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getPerformanceData($userId) {
        // First, check if the passing_grade column exists
        $checkColumnQuery = "SHOW COLUMNS FROM subjects LIKE 'passing_grade'";
        $columnResult = $this->conn->query($checkColumnQuery);
        
        if ($columnResult->num_rows > 0) {
            // If the column exists, use it in the query
            $query = "SELECT s.subject_name, 
                             AVG(g.grade) as average_grade,
                             s.passing_grade,
                             CASE WHEN AVG(g.grade) >= s.passing_grade THEN 'Pass' ELSE 'Fail' END as status
                      FROM grades g 
                      JOIN students st ON g.student_id = st.student_id
                      JOIN users u ON st.user_id = u.user_id
                      JOIN subjects s ON g.subject_id = s.subject_id
                      WHERE u.user_id = ? 
                      GROUP BY s.subject_id, s.subject_name, s.passing_grade
                      ORDER BY s.subject_name ASC";
        } else {
            // If the column doesn't exist, use a default passing grade of 70
            $query = "SELECT s.subject_name, 
                             AVG(g.grade) as average_grade,
                             70 as passing_grade,
                             CASE WHEN AVG(g.grade) >= 70 THEN 'Pass' ELSE 'Fail' END as status
                      FROM grades g 
                      JOIN students st ON g.student_id = st.student_id
                      JOIN users u ON st.user_id = u.user_id
                      JOIN subjects s ON g.subject_id = s.subject_id
                      WHERE u.user_id = ? 
                      GROUP BY s.subject_id, s.subject_name
                      ORDER BY s.subject_name ASC";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getRecentFeedback($userId) {
        $query = "SELECT f.*, u.first_name, u.last_name 
                  FROM feedback f 
                  JOIN users u ON f.instructor_id = u.user_id 
                  JOIN students s ON f.student_id = s.student_id
                  WHERE s.user_id = ? 
                  ORDER BY f.created_at DESC 
                  LIMIT 5";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getNotifications($userId) {
        $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getInstructorsForEvaluation($userId) {
        $query = "SELECT DISTINCT u.user_id as instructor_id, u.first_name, u.last_name
                  FROM users u
                  JOIN instructors i ON u.user_id = i.user_id
                  JOIN sections s ON i.instructor_id = s.instructor_id
                  JOIN section_students ss ON s.section_id = ss.section_id
                  JOIN students st ON ss.student_id = st.student_id
                  WHERE st.user_id = ? AND u.user_type = 'Instructor'
                  ORDER BY u.last_name, u.first_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

