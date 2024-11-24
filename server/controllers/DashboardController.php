<?php
// File: server/controllers/DashboardController.php

require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardController {
    private $dashboardModel;

    public function __construct($model) {
        $this->dashboardModel = $model;
    }

    public function handleRequest() {
        try {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Invalid request method. Only GET requests are allowed.');
            }

            if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
                throw new Exception('Unauthorized access. Please log in as an Instructor.');
            }

            $totalStudents = $this->dashboardModel->getTotalStudents();
            $atRiskStudents = $this->dashboardModel->getAtRiskStudentsCount();
            $performanceData = $this->dashboardModel->getAveragePerformanceByMonth(date('Y'));

            $this->sendJsonResponse([
                'status' => 'success',
                'metrics' => [
                    'total_students' => $totalStudents,
                    'at_risk_students' => $atRiskStudents,
                    'performance_data' => $performanceData
                ]
            ]);
        } catch (Exception $e) {
            $this->logError("Error in handleRequest: " . $e->getMessage());
            $this->sendJsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    private function sendJsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    private function logError($message) {
        error_log("DashboardController Error [".date('Y-m-d H:i:s')."]: $message");
    }
}

// Usage
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    require_once __DIR__ . '/../../config/db_connection.php';
    
    try {
        if (!isset($conn) || !$conn) {
            throw new Exception("Database connection failed.");
        }

        $dashboardModel = new DashboardModel($conn);
        $controller = new DashboardController($dashboardModel);
        $controller->handleRequest();
    } catch (Exception $e) {
        error_log("Fatal error: " . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'An unexpected error occurred.'
        ]);
        exit;
    }
}