<?php
// File: server/controllers/grade_management_controller.php

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

            $action = $this->getValidatedInput('action', 'string');

            switch ($action) {
                case 'fetch_sections':
                    $instructorId = $this->getValidatedInput('instructor_id', 'int');
                    $sections = $this->getSections($instructorId);
                    $this->sendJsonResponse(['status' => 'success', 'sections' => $sections]);
                    break;

                case 'fetch_subjects':
                    $sectionId = $this->getValidatedInput('section_id', 'int');
                    $subjects = $this->getSubjects($sectionId);
                    $this->sendJsonResponse(['status' => 'success', 'subjects' => $subjects]);
                    break;

                case 'fetch_grades':
                    $sectionId = $this->getValidatedInput('section_id', 'int');
                    $subjectId = $this->getValidatedInput('subject_id', 'int');
                    $quarter = $this->getValidatedInput('quarter', 'int', [1, 2, 3, 4]);
                    $grades = $this->getGrades($sectionId, $subjectId, $quarter);
                    $this->sendJsonResponse(['status' => 'success', 'grades' => $grades]);
                    break;

                case 'save_grades':
                    $grades = $this->getValidatedGrades();
                    $sectionId = $this->getValidatedInput('section_id', 'int');
                    $subjectId = $this->getValidatedInput('subject_id', 'int');
                    $quarter = $this->getValidatedInput('quarter', 'int', [1, 2, 3, 4]);
                    $academicYear = $this->getValidatedInput('academic_year', 'string');
                    $result = $this->saveGrades($grades, $sectionId, $subjectId, $quarter, $academicYear);
                    $this->sendJsonResponse($result);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            $this->logError("Error in handleRequest: " . $e->getMessage());
            $this->sendJsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    private function getValidatedInput($key, $type, $allowedValues = null) {
        $value = $_POST[$key] ?? null;

        if ($value === null) {
            throw new InvalidArgumentException("Missing required input: $key");
        }

        switch ($type) {
            case 'int':
                if (!is_numeric($value)) {
                    throw new InvalidArgumentException("Invalid $key: must be a number");
                }
                $value = intval($value);
                break;
            case 'string':
                $value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                if ($value === false) {
                    throw new InvalidArgumentException("Invalid $key: must be a string");
                }
                break;
            default:
                throw new InvalidArgumentException("Invalid type specified for input validation");
        }

        if ($allowedValues !== null && !in_array($value, $allowedValues)) {
            throw new InvalidArgumentException("Invalid $key: must be one of " . implode(', ', $allowedValues));
        }

        return $value;
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
        
        foreach ($grades as $studentId => $components) {
            foreach ($components as $componentId => $data) { // Now using component_id
                if (!isset($data['grade']) || !is_numeric($data['grade'])) {
                    throw new Exception("Invalid grade for student $studentId, component $componentId");
                }
                if (isset($data['subcategories'])) {
                    foreach ($data['subcategories'] as $subcategory) {
                        if (!isset($subcategory['name']) || !isset($subcategory['score']) || !is_numeric($subcategory['score'])) {
                            throw new Exception("Invalid subcategory data for student $studentId, component $componentId");
                        }
                    }
                }
            }
        }
        
        return $grades;
    }

    private function getSections($instructorId) {
        $stmt = $this->prepareAndExecute("SELECT section_id, section_name, school_year FROM sections WHERE instructor_id = ?", [$instructorId]);
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

    private function getGrades($sectionId, $subjectId, $quarter) {
        $stmt = $this->prepareAndExecute("
            SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                   g.component_id, g.grade, g.subcategories, gc.component_name, gc.weight,
                   sec.school_year AS academic_year
            FROM students s
            JOIN section_students ss ON s.student_id = ss.student_id
            JOIN sections sec ON ss.section_id = sec.section_id
            LEFT JOIN grades g ON s.student_id = g.student_id 
                               AND g.section_id = ? 
                               AND g.subject_id = ? 
                               AND g.quarter = ?
            LEFT JOIN grade_components gc ON g.component_id = gc.component_id
            WHERE ss.section_id = ?
            ORDER BY s.last_name, s.first_name
        ", [$sectionId, $subjectId, $quarter, $sectionId]);

        $result = $stmt->get_result();
        $grades = [];
        $academicYear = null;

        while ($row = $result->fetch_assoc()) {
            $studentId = $row['student_id'];
            $componentId = $row['component_id'];

            if (!isset($grades[$studentId])) {
                $grades[$studentId] = [
                    'student_name' => $row['student_name'],
                    'components' => []
                ];
            }

            if ($componentId) {
                $grades[$studentId]['components'][$componentId] = [
                    'grade' => $row['grade'],
                    'subcategories' => json_decode($row['subcategories'], true) ?? [],
                    'component_name' => $row['component_name'],
                    'weight' => $row['weight']
                ];
            }

            if (!$academicYear) {
                $academicYear = $row['academic_year'];
            }
        }

        return ['grades' => $grades, 'academic_year' => $academicYear];
    }

    private function saveGrades($grades, $sectionId, $subjectId, $quarter, $academicYear) {
        $this->conn->begin_transaction();
        try {
            foreach ($grades as $studentId => $components) {
                foreach ($components as $componentId => $data) { // componentId is now numeric
                    // Validate componentId exists in grade_components
                    if (!$this->componentExists($componentId)) {
                        throw new Exception("Invalid component_id: $componentId");
                    }

                    $grade = $data['grade'];
                    $subcategories = json_encode($data['subcategories'] ?? []);
                    $remarks = $this->calculateRemarks($grade);
                    $this->prepareAndExecute("
                        INSERT INTO grades (student_id, section_id, subject_id, quarter, component_id, grade, subcategories, remarks, academic_year) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE grade = VALUES(grade), subcategories = VALUES(subcategories), remarks = VALUES(remarks)
                    ", [
                        $studentId, 
                        $sectionId, 
                        $subjectId, 
                        $quarter, 
                        $componentId, // Use component_id
                        $grade, 
                        $subcategories, 
                        $remarks, 
                        $academicYear
                    ]);
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

    private function componentExists($componentId) {
        $stmt = $this->prepareAndExecute("SELECT COUNT(*) as count FROM grade_components WHERE component_id = ?", [$componentId]);
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }

    private function calculateRemarks($grade) {
        if ($grade >= 90) return 'Outstanding (O)';
        if ($grade >= 85) return 'Very Satisfactory (VS)';
        if ($grade >= 80) return 'Satisfactory (S)';
        if ($grade >= 75) return 'Fairly Satisfactory (FS)';
        return 'Did Not Meet Expectations (DME)';
    }

    private function prepareAndExecute($query, $params = []) {
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $this->conn->error);
        }
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param) || is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        return $stmt;
    }

    private function sendJsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    private function logError($message) {
        error_log("GradeManagementController Error: $message");
    }
}

// Usage
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
            'message' => 'An unexpected error occurred'
        ]);
        exit;
    }
}
?>
