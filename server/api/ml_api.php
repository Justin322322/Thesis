<?php
// File: server/api/ml_api.php

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../controllers/grade_management_controller.php';

class MachineLearningAPI {
    private $conn;
    private $gradeController;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->gradeController = new GradeManagementController($conn);
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'fetch_student_data':
                $this->fetchStudentData();
                break;
            default:
                $this->sendJsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
        }
    }

    private function fetchStudentData() {
        try {
            $sectionId = $this->getValidatedId('section_id');
            $subjectId = $this->getValidatedId('subject_id');
            $academicYear = $this->getValidatedAcademicYear();

            $studentData = $this->getStudentData($sectionId, $academicYear);
            $gradeData = $this->getGradeData($sectionId, $subjectId, $academicYear);
            $attendanceData = $this->getAttendanceData($sectionId, $subjectId, $academicYear);

            $mlData = $this->formatMachineLearningData($studentData, $gradeData, $attendanceData);

            $this->sendJsonResponse(['status' => 'success', 'data' => $mlData]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    private function getStudentData($sectionId, $academicYear) {
        $stmt = $this->conn->prepare("
            SELECT s.student_id, s.first_name, s.last_name, u.sex, u.dob
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN section_students ss ON s.student_id = ss.student_id
            WHERE ss.section_id = ? AND u.created_at <= ?
        ");
        return $this->executeAndFetchAll($stmt, "is", $sectionId, $academicYear . '-12-31');
    }

    private function getGradeData($sectionId, $subjectId, $academicYear) {
        $stmt = $this->conn->prepare("
            SELECT g.student_id, g.quarter, gc.component_name, g.grade
            FROM grades g
            JOIN grade_components gc ON g.component_id = gc.component_id
            WHERE g.section_id = ? AND g.subject_id = ? AND g.academic_year = ?
        ");
        return $this->executeAndFetchAll($stmt, "iis", $sectionId, $subjectId, $academicYear);
    }

    private function getAttendanceData($sectionId, $subjectId, $academicYear) {
        $stmt = $this->conn->prepare("
            SELECT a.student_id, a.status, COUNT(*) as count
            FROM attendance a
            WHERE a.section_id = ? AND a.subject_id = ? AND YEAR(a.date) = ?
            GROUP BY a.student_id, a.status
        ");
        return $this->executeAndFetchAll($stmt, "iii", $sectionId, $subjectId, substr($academicYear, 0, 4));
    }

    private function formatMachineLearningData($studentData, $gradeData, $attendanceData) {
        $mlData = [];

        foreach ($studentData as $student) {
            $studentId = $student['student_id'];
            $mlData[$studentId] = [
                'student_info' => $student,
                'grades' => [],
                'attendance' => [
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'excused' => 0
                ]
            ];

            // Process grades
            foreach ($gradeData as $grade) {
                if ($grade['student_id'] == $studentId) {
                    if (!isset($mlData[$studentId]['grades'][$grade['quarter']])) {
                        $mlData[$studentId]['grades'][$grade['quarter']] = [];
                    }
                    $mlData[$studentId]['grades'][$grade['quarter']][$grade['component_name']] = $grade['grade'];
                }
            }

            // Process attendance
            foreach ($attendanceData as $attendance) {
                if ($attendance['student_id'] == $studentId) {
                    $mlData[$studentId]['attendance'][strtolower($attendance['status'])] = $attendance['count'];
                }
            }

            // Calculate additional metrics
            $mlData[$studentId]['average_grade'] = $this->calculateAverageGrade($mlData[$studentId]['grades']);
            $mlData[$studentId]['attendance_rate'] = $this->calculateAttendanceRate($mlData[$studentId]['attendance']);
        }

        return array_values($mlData);
    }

    private function calculateAverageGrade($grades) {
        $totalGrade = 0;
        $count = 0;
        foreach ($grades as $quarter) {
            foreach ($quarter as $grade) {
                $totalGrade += $grade;
                $count++;
            }
        }
        return $count > 0 ? $totalGrade / $count : 0;
    }

    private function calculateAttendanceRate($attendance) {
        $total = array_sum($attendance);
        return $total > 0 ? ($attendance['present'] / $total) * 100 : 0;
    }

    private function getValidatedId($key) {
        $id = isset($_GET[$key]) ? intval($_GET[$key]) : 0;
        if ($id <= 0) {
            throw new Exception("Invalid $key provided.");
        }
        return $id;
    }

    private function getValidatedAcademicYear() {
        $academicYear = $_GET['academic_year'] ?? '';
        if (!preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
            throw new Exception('Invalid academic year format. Use YYYY-YYYY.');
        }
        return $academicYear;
    }

    private function executeAndFetchAll($stmt, $types, ...$params) {
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    private function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Usage
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    $api = new MachineLearningAPI($conn);
    $api->handleRequest();
}