<?php
// File: server/controllers/grade_management_controller.php

class GradeManagementController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function getInstructorId($userId) {
        $stmt = $this->conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $instructorData = $result->fetch_assoc();
        $stmt->close();
        return isset($instructorData['instructor_id']) ? $instructorData['instructor_id'] : 0;
    }

    public function getSections($instructorId) {
        try {
            $stmt = $this->conn->prepare("SELECT s.section_id, s.section_name
                                        FROM sections s 
                                        WHERE s.instructor_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $instructorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $sections = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $sections;
        } catch (Exception $e) {
            error_log("Error in getSections: " . $e->getMessage());
            return [];
        }
    }

    public function getSubjects($sectionId) {
        try {
            $stmt = $this->conn->prepare("SELECT su.subject_id, su.subject_name
                                        FROM subjects su 
                                        JOIN section_subjects ss ON su.subject_id = ss.subject_id
                                        WHERE ss.section_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("i", $sectionId);
            $stmt->execute();
            $result = $stmt->get_result();
            $subjects = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $subjects;
        } catch (Exception $e) {
            error_log("Error in getSubjects: " . $e->getMessage());
            return [];
        }
    }

    public function getStudents($sectionId) {
        try {
            $stmt = $this->conn->prepare("SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) as student_name
                                        FROM students s 
                                        JOIN section_students ss ON s.student_id = ss.student_id 
                                        WHERE ss.section_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("i", $sectionId);
            $stmt->execute();
            $result = $stmt->get_result();
            $students = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $students;
        } catch (Exception $e) {
            error_log("Error in getStudents: " . $e->getMessage());
            return [];
        }
    }

    public function getGrades($sectionId, $subjectId, $quarter) {
        try {
            $stmt = $this->conn->prepare("SELECT g.student_id, g.component, g.grade, g.subcategories, g.remarks
                                        FROM grades g 
                                        WHERE g.section_id = ? AND g.subject_id = ? AND g.quarter = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("iis", $sectionId, $subjectId, $quarter);
            $stmt->execute();
            $result = $stmt->get_result();
            $gradesData = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $grades = [];
            foreach ($gradesData as $grade) {
                $grades[$grade['student_id']][$grade['component']] = [
                    'grade' => $grade['grade'],
                    'subcategories' => json_decode($grade['subcategories'], true),
                    'remarks' => $grade['remarks']
                ];
            }
            return $grades;
        } catch (Exception $e) {
            error_log("Error in getGrades: " . $e->getMessage());
            return [];
        }
    }

    public function saveGrades($grades, $detailedGrades, $sectionId, $subjectId, $quarter) {
        try {
            $this->conn->begin_transaction();

            foreach ($grades as $studentId => $componentGrades) {
                foreach ($componentGrades as $component => $gradeData) {
                    $grade = $gradeData['grade'];
                    $subcategories = isset($detailedGrades[$studentId][$component]['subcategories']) 
                        ? json_encode($detailedGrades[$studentId][$component]['subcategories']) 
                        : null;
                    $remarks = $this->calculateRemarks($grade);

                    $stmt = $this->conn->prepare("INSERT INTO grades 
                                                (student_id, section_i

d, subject_id, quarter, component, grade, subcategories, remarks) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                                ON DUPLICATE KEY UPDATE 
                                                grade = VALUES(grade),
                                                subcategories = VALUES(subcategories),
                                                remarks = VALUES(remarks)");
                    
                    if (!$stmt) {
                        throw new Exception("Failed to prepare statement: " . $this->conn->error);
                    }

                    $stmt->bind_param("iiissdss", $studentId, $sectionId, $subjectId, $quarter, $component, $grade, $subcategories, $remarks);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Grades saved successfully'];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in saveGrades: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to save grades: ' . $e->getMessage()];
        }
    }

    private function calculateRemarks($grade) {
        return $grade >= 75 ? 'Passed' : 'Failed';
    }

    public function handleRequest() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'fetch_sections':
                    $instructorId = $_POST['instructor_id'] ?? '';
                    $this->sendJsonResponse([
                        'status' => 'success',
                        'sections' => $this->getSections($instructorId)
                    ]);
                    break;

                case 'fetch_subjects':
                    $sectionId = $_POST['section_id'] ?? '';
                    $this->sendJsonResponse([
                        'status' => 'success',
                        'subjects' => $this->getSubjects($sectionId)
                    ]);
                    break;

                case 'fetch_students':
                    $sectionId = $_POST['section_id'] ?? '';
                    $this->sendJsonResponse([
                        'status' => 'success',
                        'students' => $this->getStudents($sectionId)
                    ]);
                    break;

                case 'fetch_grades':
                    $sectionId = $_POST['section_id'] ?? '';
                    $subjectId = $_POST['subject_id'] ?? '';
                    $quarter = $_POST['quarter'] ?? '';
                    $grades = $this->getGrades($sectionId, $subjectId, $quarter);
                    $this->sendJsonResponse([
                        'status' => 'success',
                        'grades' => $grades
                    ]);
                    break;

                case 'save_grades':
                    $grades = json_decode($_POST['grades'], true);
                    $detailedGrades = json_decode($_POST['detailed_grades'], true);
                    $sectionId = $_POST['section_id'] ?? '';
                    $subjectId = $_POST['subject_id'] ?? '';
                    $quarter = $_POST['quarter'] ?? '';
                    $result = $this->saveGrades($grades, $detailedGrades, $sectionId, $subjectId, $quarter);
                    $this->sendJsonResponse($result);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            error_log("Error in handleRequest: " . $e->getMessage());
            $this->sendJsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

// Handle the request
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