<?php
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: /AcadMeter/public/login.html');
    exit;
}

require_once '../../config/db_connection.php';
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Handle the request
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if (!$action) {
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit;
}

// Available actions and their respective handlers
switch ($action) {
    case 'overview':
        getDashboardStats($conn);
        break;
    case 'pending_users':
        getPendingUsers($conn);
        break;
    case 'delete_users_list':
        getApprovedUsers($conn);
        break;
    case 'delete_user':
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['userId'])) {
            deleteUser($conn, $data['userId']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
        }
        break;
    case 'update_user_status':
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['userId']) && isset($data['userAction'])) {
            updateUserStatus($conn, $data['userId'], $data['userAction']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
        }
        break;
    case 'get_notifications':
        getNotifications($conn);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

// Function to fetch dashboard overview stats
function getDashboardStats($conn) {
    try {
        $data = [
            'total_users' => $conn->query("SELECT COUNT(*) as total_users FROM users")->fetch_assoc()['total_users'] ?? 0,
            'pending_approvals' => $conn->query("SELECT COUNT(*) as pending_approvals FROM users WHERE status = 'pending'")->fetch_assoc()['pending_approvals'] ?? 0,
            'audit_logs' => $conn->query("SELECT COUNT(*) as audit_logs FROM action_logs")->fetch_assoc()['audit_logs'] ?? 0,
            'reports_generated' => $conn->query("SELECT COUNT(*) as reports_generated FROM reports")->fetch_assoc()['reports_generated'] ?? 0
        ];
        echo json_encode($data);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching dashboard stats: ' . $e->getMessage()]);
    }
}

// Function to fetch pending users
function getPendingUsers($conn) {
    try {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, user_type, email, status FROM users WHERE status = 'pending'");
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching pending users: ' . $e->getMessage()]);
    }
}

// Function to fetch approved users for deletion
function getApprovedUsers($conn) {
    try {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, user_type, email FROM users WHERE status = 'approved' AND user_type IN ('Instructor', 'Student')");
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching approved users: ' . $e->getMessage()]);
    }
}

// Function to update user status and handle notifications and activity logs
function updateUserStatus($conn, $userId, $action) {
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    try {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $status, $userId);
        $stmt->execute();

        if ($action === 'approve') {
            $userTypeQuery = $conn->prepare("SELECT user_type FROM users WHERE user_id = ?");
            $userTypeQuery->bind_param("i", $userId);
            $userTypeQuery->execute();
            $userType = $userTypeQuery->get_result()->fetch_assoc()['user_type'];

            $identifier = str_pad($userId, 4, "0", STR_PAD_LEFT);

            if ($userType === 'Student') {
                $studentStmt = $conn->prepare("INSERT IGNORE INTO students (user_id, student_number) VALUES (?, ?)");
                $studentNumber = "STU-" . $identifier;
                $studentStmt->bind_param("is", $userId, $studentNumber);
                $studentStmt->execute();
            } elseif ($userType === 'Instructor') {
                $instructorStmt = $conn->prepare("INSERT IGNORE INTO instructors (user_id, employee_number) VALUES (?, ?)");
                $employeeNumber = "EMP-" . $identifier;
                $instructorStmt->bind_param("is", $userId, $employeeNumber);
                $instructorStmt->execute();
            }
        }

        // Insert notification and activity log
        $message = ($status === 'approved') ? "User ID $userId has been approved." : "User ID $userId has been rejected.";
        $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, message, notification_type) VALUES (?, ?, 'account_approval')");
        $notificationStmt->bind_param("is", $userId, $message);
        $notificationStmt->execute();

        // Log action
        $logMessage = ucfirst($status) . " action taken for User ID $userId.";
        $logStmt = $conn->prepare("INSERT INTO action_logs (user_id, action_type, description) VALUES (?, 'Account Approval', ?)");
        $logStmt->bind_param("is", $userId, $logMessage);
        $logStmt->execute();

        sendEmailNotification($status, $userId, $conn);
        echo json_encode(['status' => 'success', 'message' => ucfirst($status) . ' user successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update user status: ' . $e->getMessage()]);
    }
}

// Function to send email notifications
function sendEmailNotification($status, $userId, $conn) {
    $emailQuery = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
    $emailQuery->bind_param("i", $userId);
    $emailQuery->execute();
    $userData = $emailQuery->get_result()->fetch_assoc();

    $mail = new PHPMailer(true);
    try {
        // Set up mailer configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'justinmarlosibonga@gmail.com';
        $mail->Password = 'mvnhppaolniedhvv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('no-reply@example.com', 'AcadMeter Admin');
        $mail->addAddress($userData['email']);

        $mail->Subject = $status === 'approved' ? "Approval Notification" : "Rejection Notification";
        $mail->Body = "Dear " . $userData['first_name'] . ",\n\n" . ($status === 'approved' ? "Your registration has been approved. Welcome!" : "Your registration has been declined.");

        $mail->send();
    } catch (Exception $e) {
        error_log("Error sending email: " . $e->getMessage());
    }
}

// Function to delete a user
function deleteUser($conn, $userId) {
    try {
        $deleteUser = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $deleteUser->bind_param("i", $userId);
        $deleteUser->execute();

        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user: ' . $e->getMessage()]);
    }
}
// Function to fetch notifications for the admin
function getNotifications($conn) {
    try {
        $stmt = $conn->prepare("SELECT message, timestamp FROM notifications ORDER BY timestamp DESC LIMIT 10");
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($notifications);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching notifications: ' . $e->getMessage()]);
    }
}

?>
