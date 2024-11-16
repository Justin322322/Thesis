<?php
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: /AcadMeter/public/login.php');
    exit;
}

require_once '../../config/db_connection.php';
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Handle the request based on the 'action' parameter
$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? ($_GET['action'] ?? ($_POST['action'] ?? ''));

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
        if (isset($data['userId'])) {
            deleteUser($conn, $data['userId']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
        }
        break;
    case 'update_user_status':
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

/**
 * Function to fetch dashboard overview stats
 */
function getDashboardStats($conn) {
    try {
        $data = [
            'total_users' => $conn->query("SELECT COUNT(*) as total_users FROM users")->fetch_assoc()['total_users'] ?? 0,
            'pending_approvals' => $conn->query("SELECT COUNT(*) as pending_approvals FROM users WHERE status = 'pending' AND verified = TRUE")->fetch_assoc()['pending_approvals'] ?? 0,
            'audit_logs' => $conn->query("SELECT COUNT(*) as audit_logs FROM action_logs")->fetch_assoc()['audit_logs'] ?? 0,
            'reports_generated' => $conn->query("SELECT COUNT(*) as reports_generated FROM reports")->fetch_assoc()['reports_generated'] ?? 0
        ];
        echo json_encode($data);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching dashboard stats: ' . $e->getMessage()]);
    }
}

/**
 * Function to fetch pending users who are verified
 */
function getPendingUsers($conn) {
    try {
        // Fetch users with status 'pending' and verified = TRUE
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, user_type, email, status FROM users WHERE status = 'pending' AND verified = TRUE");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching pending users: ' . $e->getMessage()]);
    }
}

/**
 * Function to fetch approved users available for deletion
 */
function getApprovedUsers($conn) {
    try {
        // Fetch users with status 'approved' and user_type 'Instructor' or 'Student'
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, user_type, email FROM users WHERE status = 'approved' AND user_type IN ('Instructor', 'Student')");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching approved users: ' . $e->getMessage()]);
    }
}

/**
 * Function to delete a user
 */
function deleteUser($conn, $userId) {
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        error_log("Starting deletion process for user ID: $userId");
        
        // Check if the user exists and get their user type
        $checkStmt = $conn->prepare("SELECT user_id, user_type FROM users WHERE user_id = ?");
        if (!$checkStmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $checkStmt->bind_param("i", $userId);
        if (!$checkStmt->execute()) {
            throw new Exception("Execute failed: (" . $checkStmt->errno . ") " . $checkStmt->error);
        }
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("User not found.");
        }
        
        $user = $result->fetch_assoc();
        $userType = $user['user_type'];
        
        error_log("User found. Type: $userType");

        // Log action
        $logMessage = "User ID $userId of type $userType has been deleted.";
        $logStmt = $conn->prepare("INSERT INTO action_logs (user_id, action_type, description) VALUES (?, 'User Deletion', ?)");
        if (!$logStmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $logStmt->bind_param("is", $userId, $logMessage);
        if (!$logStmt->execute()) {
            throw new Exception("Execute failed: (" . $logStmt->errno . ") " . $logStmt->error);
        }

        // Delete from specific user type table (students, instructors, or admins)
        switch ($userType) {
            case 'Student':
                $stmt = $conn->prepare("DELETE FROM students WHERE user_id = ?");
                break;
            case 'Instructor':
                $stmt = $conn->prepare("DELETE FROM instructors WHERE user_id = ?");
                break;
            case 'Admin':
                $stmt = $conn->prepare("DELETE FROM admins WHERE user_id = ?");
                break;
            default:
                throw new Exception("Invalid user type: $userType");
        }
        
        if (!$stmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            error_log("No rows affected when deleting from $userType table for user ID: $userId");
        } else {
            error_log("Successfully deleted from $userType table for user ID: $userId");
        }
        
        // Delete from users table
        $deleteUserStmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        if (!$deleteUserStmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $deleteUserStmt->bind_param("i", $userId);
        if (!$deleteUserStmt->execute()) {
            throw new Exception("Execute failed: (" . $deleteUserStmt->errno . ") " . $deleteUserStmt->error);
        }
        
        if ($deleteUserStmt->affected_rows === 0) {
            throw new Exception("Failed to delete user from users table. User ID: $userId");
        }
        
        error_log("Successfully deleted user from users table. User ID: $userId");
        
        // Commit transaction
        $conn->commit();
        
        error_log("User deletion process completed successfully for User ID: $userId");
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error in deleteUser function: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user: ' . $e->getMessage()]);
    }
}

/**
 * Function to update user status (approve/reject)
 */
function updateUserStatus($conn, $userId, $action) {
    try {
        error_log("Updating user status: User ID = $userId, Action = $action");
        // Check if the user is verified
        $verifyStmt = $conn->prepare("SELECT verified, status FROM users WHERE user_id = ?");
        $verifyStmt->bind_param("i", $userId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        if ($verifyResult->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'User not found.']);
            return;
        }
        $user = $verifyResult->fetch_assoc();
        $verifyStmt->close();
        
        if (!$user['verified']) {
            echo json_encode(['status' => 'error', 'message' => 'User is not verified and cannot be updated.']);
            return;
        }

        // Determine new status based on action
        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
        
        // Check if the status is already set to the new status
        if ($user['status'] === $newStatus) {
            echo json_encode(['status' => 'error', 'message' => "User is already {$newStatus}."]);
            return;
        }

        // Update user status
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $newStatus, $userId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $message = ($newStatus === 'approved') ? "User has been successfully approved." : "User has been successfully rejected.";
            error_log("User status updated successfully: $message");
            // Insert notification for the user
            $notificationMessage = ($newStatus === 'approved') ? "Your account has been approved." : "Your account has been rejected.";
            $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, message, notification_type) VALUES (?, ?, 'account_approval')");
            $notificationStmt->bind_param("is", $userId, $notificationMessage);
            $notificationStmt->execute();
            $notificationStmt->close();
        
            // Log action
            $logMessage = ucfirst($newStatus) . " action taken for User ID $userId.";
            $logStmt = $conn->prepare("INSERT INTO action_logs (user_id, action_type, description) VALUES (?, 'Account Approval', ?)");
            $logStmt->bind_param("is", $userId, $logMessage);
            $logStmt->execute();
            $logStmt->close();
        
            // Send email notification to the user
            sendEmailNotification($newStatus, $userId, $conn);
        
            echo json_encode(['status' => 'success', 'message' => $message]);
        } else {
            $errorMessage = 'No changes were made. User status might already be set to ' . $newStatus . '.';
            error_log("Error updating user status: $errorMessage");
            echo json_encode(['status' => 'error', 'message' => $errorMessage]);
        }
    } catch (Exception $e) {
        $errorMessage = 'Failed to update user status: ' . $e->getMessage();
        error_log("Exception in updateUserStatus: $errorMessage");
        echo json_encode(['status' => 'error', 'message' => $errorMessage]);
    }
}

/**
 * Function to send email notifications to users
 */
function sendEmailNotification($status, $userId, $conn) {
    // Fetch user's email and first name
    $emailQuery = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
    $emailQuery->bind_param("i", $userId);
    $emailQuery->execute();
    $userData = $emailQuery->get_result()->fetch_assoc();
    $emailQuery->close();

    if (!$userData) {
        // User not found, cannot send email
        return;
    }

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                 // Enable SMTP authentication
        $mail->Username   = 'justinmarlosibonga@gmail.com';       // SMTP username
        $mail->Password   = 'mvnhppaolniedhvv';                  // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Enable TLS encryption; PHPMailer::ENCRYPTION_SMTPS encouraged
        $mail->Port       = 587;                                  // TCP port to connect to

        // Recipients
        $mail->setFrom('no-reply@acadmeter.com', 'AcadMeter Admin');
        $mail->addAddress($userData['email'], $userData['first_name']);     // Add a recipient

        // Content
        $mail->isHTML(false);                                  // Set email format to plain text
        $mail->Subject = ($status === 'approved') ? 'Account Approved' : 'Account Rejected';
        $mail->Body    = ($status === 'approved') ?
            "Dear {$userData['first_name']},\n\nYour account has been approved. Welcome to AcadMeter!\n\nBest regards,\nAcadMeter Team" :
            "Dear {$userData['first_name']},\n\nWe regret to inform you that your account has been rejected.\n\nBest regards,\nAcadMeter Team";

        $mail->send();
    } catch (Exception $e) {
        // Log email sending failure
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

/**
 * Function to fetch latest notifications
 */
function getNotifications($conn) {
    try {
        // Fetch the latest 10 notifications
        $stmt = $conn->prepare("SELECT message, timestamp FROM notifications ORDER BY timestamp DESC LIMIT 10");
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($notifications);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching notifications: ' . $e->getMessage()]);
    }
}
?>