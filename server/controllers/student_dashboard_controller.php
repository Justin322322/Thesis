<?php
if (!defined('IN_STUDENT_DASHBOARD')) {
    exit('Direct script access denied.');
}

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../models/StudentModel.php';

class StudentDashboardController {
    private $conn;
    private $studentModel;

    public function __construct($db) {
        $this->conn = $db;
        $this->studentModel = new StudentModel($this->conn);
    }

    public function getStudentData($userId) {
        return $this->studentModel->getStudentData($userId);
    }

    public function getGradeSummary($userId) {
        return $this->studentModel->getGradeSummary($userId);
    }

    public function getDetailedGrades($userId) {
        return $this->studentModel->getDetailedGrades($userId);
    }

    public function getPerformanceData($userId) {
        return $this->studentModel->getPerformanceData($userId);
    }

    public function getRecentFeedback($userId) {
        return $this->studentModel->getRecentFeedback($userId);
    }

    public function getNotifications($userId) {
        return $this->studentModel->getNotifications($userId);
    }

    public function getInstructorsForEvaluation($userId) {
        return $this->studentModel->getInstructorsForEvaluation($userId);
    }
}

// Initialize the controller
$studentDashboardController = new StudentDashboardController($conn);

