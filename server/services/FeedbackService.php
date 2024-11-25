<?php
// server/services/FeedbackService.php

require_once __DIR__ . '/../../config/db_connection.php';

class FeedbackService {
    private mysqli $conn;
    private int $instructorId;

    public function __construct(mysqli $conn, int $instructorId) {
        $this->conn = $conn;
        $this->instructorId = $instructorId;
    }

    /**
     * Get students assigned to the instructor
     */
    public function getAssignedStudents(): array {
        try {
            $query = "
                SELECT DISTINCT 
                    s.student_id,
                    s.first_name,
                    s.last_name,
                    GROUP_CONCAT(DISTINCT sec.section_name) AS sections
                FROM students s
                INNER JOIN section_students ss ON s.student_id = ss.student_id
                INNER JOIN sections sec ON ss.section_id = sec.section_id
                GROUP BY s.student_id, s.first_name, s.last_name
                ORDER BY s.last_name, s.first_name
            ";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $this->conn->error);
            }

            // If you want to filter students assigned to the instructor's sections
            // Uncomment the following lines and adjust the query accordingly
            /*
            $query .= "
                WHERE sec.instructor_id = ?
            ";
            $stmt->bind_param('i', $this->instructorId);
            */

            $stmt->execute();
            $result = $stmt->get_result();

            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = [
                    'student_id' => (int) $row['student_id'],
                    'first_name' => $row['first_name'],
                    'last_name'  => $row['last_name'],
                    'sections'   => $row['sections']
                ];
            }

            return [
                'success' => true,
                'data'    => $students
            ];
        } catch (Exception $e) {
            error_log("Error fetching students: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'Failed to fetch students'
            ];
        }
    }

    /**
     * Submit feedback for a student
     */
    public function submitFeedback(int $studentId, string $feedbackText): array {
        try {
            // Validate input
            $this->validateFeedback($studentId, $feedbackText);

            // Begin transaction
            $this->conn->begin_transaction();

            // Insert feedback
            $stmt = $this->conn->prepare("
                INSERT INTO feedback (student_id, instructor_id, feedback_text, created_at) 
                VALUES (?, ?, ?, NOW())
            ");

            if (!$stmt) {
                throw new Exception('Failed to prepare feedback statement: ' . $this->conn->error);
            }

            $stmt->bind_param("iis", $studentId, $this->instructorId, $feedbackText);

            if (!$stmt->execute()) {
                throw new Exception('Failed to submit feedback');
            }

            // Commit transaction
            $this->conn->commit();
            return ['success' => true, 'message' => 'Feedback submitted successfully'];
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error submitting feedback: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get feedback history for a student
     */
    public function getFeedbackHistory(int $studentId): array {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    f.feedback_id,
                    f.feedback_text,
                    f.created_at,
                    CONCAT(i.first_name, ' ', i.last_name) AS instructor_name
                FROM feedback f
                JOIN instructors i ON f.instructor_id = i.instructor_id
                WHERE f.student_id = ?
                ORDER BY f.created_at DESC
            ");

            if (!$stmt) {
                throw new Exception('Failed to prepare feedback history statement: ' . $this->conn->error);
            }

            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();

            $feedback = [];
            while ($row = $result->fetch_assoc()) {
                $feedback[] = [
                    'feedback_id'     => (int) $row['feedback_id'],
                    'feedback_text'   => $row['feedback_text'],
                    'created_at'      => $row['created_at'],
                    'instructor_name' => $row['instructor_name']
                ];
            }

            return ['success' => true, 'data' => $feedback];
        } catch (Exception $e) {
            error_log("Error fetching feedback history: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to fetch feedback history'];
        }
    }

    /**
     * Validate feedback input
     */
    private function validateFeedback(int $studentId, string $feedbackText): void {
        if ($studentId <= 0) {
            throw new Exception('Invalid student ID');
        }

        if (empty(trim($feedbackText))) {
            throw new Exception('Feedback text is required');
        }

        if (strlen($feedbackText) > 65535) { // Maximum length for TEXT column
            throw new Exception('Feedback text must be less than 65,535 characters');
        }

        // Optional: Verify that the student exists
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS count FROM students WHERE student_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $this->conn->error);
        }

        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] == 0) {
            throw new Exception('Student does not exist');
        }
    }
}