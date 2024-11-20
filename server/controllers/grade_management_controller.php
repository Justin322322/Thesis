<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\grade_management_controller.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/error_log.log');

class GradeManagementController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handleRequest() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'fetch_sections':
                    $instructorId = $this->getValidatedId('instructor_id');
                    $sections = $this->getSections($instructorId);
                    $this->sendJsonResponse(['status' => 'success', 'sections' => $sections]);
                    break;

                case 'fetch_subjects':
                    $sectionId = $this->getValidatedId('section_id');
                    $subjects = $this->getSubjects($sectionId);
                    $this->sendJsonResponse(['status' => 'success', 'subjects' => $subjects]);
                    break;

                case 'fetch_students':
                    $sectionId = $this->getValidatedId('section_id');
                    $students = $this->getStudents($sectionId);
                    $this->sendJsonResponse(['status' => 'success', 'students' => $students]);
                    break;

                case 'fetch_grades':
                    $sectionId = $this->getValidatedId('section_id');
                    $subjectId = $this->getValidatedId('subject_id');
                    $quarter = $this->getValidatedQuarter();
                    $academicYear = $this->getValidatedAcademicYear();
                    $grades = $this->getGrades($sectionId, $subjectId, $quarter, $academicYear);
                    $this->sendJsonResponse(['status' => 'success', 'grades' => $grades]);
                    break;

                case 'save_grades':
                    $grades = $this->getValidatedGrades();
                    $sectionId = $this->getValidatedId('section_id');
                    $subjectId = $this->getValidatedId('subject_id');
                    $quarter = $this->getValidatedQuarter();
                    $academicYear = $this->getValidatedAcademicYear();
                    $result = $this->saveGrades($grades, $sectionId, $subjectId, $quarter, $academicYear);
                    $this->sendJsonResponse($result);
                    break;

                case 'fetch_subcategories':
                    $componentKey = $this->getValidatedComponentKey();
                    $subcategories = $this->getSubcategories($componentKey);
                    $this->sendJsonResponse(['status' => 'success', 'subcategories' => $subcategories]);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            $this->logError("Error in handleRequest: " . $e->getMessage());
            $this->sendJsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function getInstructorId($userId) {
        $stmt = $this->prepareAndExecute("SELECT instructor_id FROM instructors WHERE user_id = ?", [$userId]);
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['instructor_id'];
        }
        return null;
    }

    private function getValidatedId($key) {
        $id = $_POST[$key] ?? null;
        if (!$id || !is_numeric($id)) {
            throw new Exception("Invalid $key: must be a number");
        }
        return intval($id);
    }

    private function getValidatedQuarter() {
        $quarter = $_POST['quarter'] ?? null;
        if (!$quarter || !in_array($quarter, [1, 2, 3, 4])) {
            throw new Exception('Invalid quarter');
        }
        return intval($quarter);
    }

    private function getValidatedAcademicYear() {
        $academicYear = $_POST['academic_year'] ?? null;
        if (!$academicYear || !preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
            throw new Exception('Invalid academic year');
        }
        return $academicYear;
    }

    private function getValidatedGrades() {
        $gradesJson = $_POST['grades'] ?? null;
        if (!$gradesJson) {
            throw new Exception('No grades data provided');
        }
        $grades = json_decode($gradesJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid grades data: ' . json_last_error_msg());
        }
        return $grades;
    }

    private function getValidatedComponentKey() {
        $componentKey = $_POST['component_key'] ?? null;
        if (!$componentKey || !is_string($componentKey)) {
            throw new Exception('Invalid component key');
        }
        return $componentKey;
    }

    private function getSections($instructorId) {
        $stmt = $this->prepareAndExecute("SELECT section_id, section_name FROM sections WHERE instructor_id = ?", [$instructorId]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getSubjects($sectionId) {
        $stmt = $this->prepareAndExecute("
            SELECT s.subject_id, s.subject_name 
            FROM subjects s
            JOIN section_subjects ss ON s.subject_id = ss.subject_id
            WHERE ss.section_id = ?
        ", [$sectionId]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getStudents($sectionId) {
        $stmt = $this->prepareAndExecute("
            SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name
            FROM students s
            JOIN section_students ss ON s.student_id = ss.student_id
            WHERE ss.section_id = ?
        ", [$sectionId]);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getGrades($sectionId, $subjectId, $quarter, $academicYear) {
        $stmt = $this->prepareAndExecute("
            SELECT student_id, component_id, grade, subcategories 
            FROM grades 
            WHERE section_id = ? AND subject_id = ? AND quarter = ? AND academic_year = ?
        ", [$sectionId, $subjectId, $quarter, $academicYear]);
        $result = $stmt->get_result();
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $studentId = $row['student_id'];
            $componentId = $row['component_id'];
            $grades[$studentId][$componentId] = [
                'grade' => $row['grade'],
                'subcategories' => json_decode($row['subcategories'], true) ?? []
            ];
        }
        return $grades;
    }

    private function saveGrades($grades, $sectionId, $subjectId, $quarter, $academicYear) {
        $this->conn->begin_transaction();
        try {
            foreach ($grades as $studentId => $components) {
                foreach ($components as $componentId => $data) {
                    $grade = $data['grade'];
                    $subcategories = json_encode($data['subcategories']);
                    $this->prepareAndExecute("
                        INSERT INTO grades (student_id, section_id, subject_id, component_id, quarter, academic_year, grade, subcategories) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE grade = ?, subcategories = ?
                    ", [$studentId, $sectionId, $subjectId, $componentId, $quarter, $academicYear, $grade, $subcategories, $grade, $subcategories]);
                }
            }
            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Grades saved successfully'];
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->logError("Error saving grades: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to save grades: ' . $e->getMessage()];
        }
    }

    private function getSubcategories($componentKey) {
        // This is a placeholder. In a real application, you would fetch this from a database.
        $subcategories = [
            'written_works' => [
                ['name' => 'Quiz 1', 'description' => 'First quiz of the quarter'],
                ['name' => 'Quiz 2', 'description' => 'Second quiz of the quarter'],
                ['name' => 'Assignment', 'description' => 'Homework assignment'],
            ],
            'performance_tasks' => [
                ['name' => 'Project 1', 'description' => 'First project of the quarter'],
                ['name' => 'Presentation', 'description' => 'Oral presentation'],
            ],
            'quarterly_assessment' => [
                ['name' => 'Final Exam', 'description' => 'End of quarter examination'],
            ],
        ];

        return $subcategories[$componentKey] ?? [];
    }

    private function sendJsonResponse($data, $statusCode = 200) {
        ob_clean();
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function prepareAndExecute($query, $params = []) {
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        return $stmt;
    }

    private function logError($message) {
        error_log($message);
    }
}

// Handle the request if this file is accessed directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    require_once __DIR__ . '/../../config/db_connection.php';
    
    try {
        if (!isset($conn) || !$conn) {
            throw new Exception("Database connection failed");
        }
        $controller = new GradeManagementController($conn);
        $controller->handleRequest();
    } catch (Exception $e) {
        error_log("Fatal error: " . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'An unexpected error occurred: ' . $e->getMessage()
        ]);
        exit;
    }
}