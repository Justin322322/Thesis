<?php
// File: server/models/PredictiveAnalyticsModel.php

class PredictiveAnalyticsModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function fetchAtRiskStudentsWithSubjects($instructorId) {
        $threshold = 75;

        $query = "
            SELECT 
                s.student_id,
                CONCAT(s.first_name, ' ', s.last_name) AS name,
                sub.subject_name,
                AVG(g.grade) AS average_grade
            FROM students s
            JOIN section_students ss ON s.student_id = ss.student_id
            JOIN sections sec ON ss.section_id = sec.section_id
            JOIN grades g ON s.student_id = g.student_id 
                          AND g.section_id = sec.section_id
            JOIN subjects sub ON g.subject_id = sub.subject_id
            WHERE sec.instructor_id = ?
            GROUP BY s.student_id, s.first_name, s.last_name, sub.subject_name
            HAVING AVG(g.grade) < ?
            ORDER BY s.last_name, s.first_name, sub.subject_name ASC
        ";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $this->conn->error);
        }

        $stmt->bind_param("ii", $instructorId, $threshold);

        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        $atRiskStudents = [];

        while ($row = $result->fetch_assoc()) {
            $studentId = $row['student_id'];
            $subjectName = $row['subject_name'];
            $averageGrade = round($row['average_grade'], 2);

            if (!isset($atRiskStudents[$studentId])) {
                $atRiskStudents[$studentId] = [
                    'student_id' => $studentId,
                    'name' => $row['name'],
                    'average_final_grade' => $averageGrade,
                    'risk_probability' => $this->calculateRiskProbability($averageGrade),
                    'suggested_intervention' => $this->getSuggestedIntervention($averageGrade),
                    'failing_subjects' => []
                ];
            }

            $atRiskStudents[$studentId]['failing_subjects'][] = $subjectName;
        }

        $stmt->close();

        return array_values($atRiskStudents); // Reset keys to have a sequential array
    }

    private function calculateRiskProbability($averageGrade) {
        $threshold = 75;
        $riskDifference = $threshold - $averageGrade;
        $riskProbability = ($riskDifference / $threshold) * 100;
        return min(100, max(0, round($riskProbability, 2))); // Ensure it's between 0 and 100
    }

    private function getSuggestedIntervention($averageGrade) {
        if ($averageGrade < 60) {
            return 'Immediate Intervention: Schedule one-on-one tutoring sessions.';
        } elseif ($averageGrade < 70) {
            return 'High Priority: Provide additional resources and monitor progress closely.';
        } elseif ($averageGrade < 75) {
            return 'Moderate Priority: Encourage participation in study groups and workshops.';
        } else {
            return 'Low Priority: Maintain regular communication and provide standard support.';
        }
    }

    private function logError($message) {
        error_log("PredictiveAnalyticsModel Error [".date('Y-m-d H:i:s')."]: $message");
    }
}
?>
