<?php
// File: server/controllers/PredictiveAnalyticsController.php

require_once __DIR__ . '/../models/PredictiveAnalyticsModel.php';

class PredictiveAnalyticsController {
    private $predictiveAnalyticsModel;

    public function __construct($model) {
        $this->predictiveAnalyticsModel = $model;
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

            $instructorId = intval($_SESSION['user_id']);

            $atRiskStudents = $this->predictiveAnalyticsModel->fetchAtRiskStudentsWithSubjects($instructorId);

            $this->sendJsonResponse([
                'status' => 'success',
                'at_risk_students' => $atRiskStudents
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
        error_log("PredictiveAnalyticsController Error [".date('Y-m-d H:i:s')."]: $message");
    }
}

// Standalone Execution
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    require_once __DIR__ . '/../../config/db_connection.php';
    require_once __DIR__ . '/../models/PredictiveAnalyticsModel.php';
    require_once __DIR__ . '/PredictiveAnalyticsController.php';

    try {
        if (!isset($conn) || !$conn) {
            throw new Exception("Database connection failed.");
        }

        $predictiveAnalyticsModel = new PredictiveAnalyticsModel($conn);
        $controller = new PredictiveAnalyticsController($predictiveAnalyticsModel);
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
?>
