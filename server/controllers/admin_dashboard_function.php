<?php
// File: C:\xampp\htdocs\AcadMeter\server\controllers\admin_dashboard_function.php

session_start();

// Include necessary libraries
require_once '../../config/db_connection.php';
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

header('Content-Type: application/json');

// Retrieve input data
$input = json_decode(file_get_contents("php://input"), true);
$action = $input['action'] ?? ($_GET['action'] ?? ($_POST['action'] ?? ''));

// Handle the request based on the 'action' parameter
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
        if (isset($input['userId'])) {
            deleteUser($conn, $input['userId']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
        }
        break;
    case 'update_user_status':
        if (isset($input['userId']) && isset($input['userAction'])) {
            updateUserStatus($conn, $input['userId'], $input['userAction'], $input);
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
 * Fetch and return dashboard statistics.
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
 * Retrieve and return pending users.
 */
function getPendingUsers($conn) {
    try {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, user_type, email, status FROM users WHERE status = 'pending' AND verified = TRUE");
        if (!$stmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching pending users: ' . $e->getMessage()]);
    }
}

/**
 * Retrieve and return approved users.
 */
function getApprovedUsers($conn) {
    try {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, user_type, email FROM users WHERE status = 'approved' AND user_type IN ('Instructor', 'Student')");
        if (!$stmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching approved users: ' . $e->getMessage()]);
    }
}

/**
 * Delete a user from the system.
 */
function deleteUser($conn, $userId) {
    try {
        $conn->begin_transaction();

        error_log("Starting deletion process for user ID: $userId");

        // Fetch user details
        $stmt = $conn->prepare("SELECT user_id, user_type FROM users WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        }
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("User not found.");
        }

        $user = $result->fetch_assoc();
        $userType = $user['user_type'];

        error_log("User found. Type: $userType");

        // Log the deletion
        $logMessage = "User ID $userId of type $userType has been deleted.";
        $logStmt = $conn->prepare("INSERT INTO action_logs (user_id, action_type, description) VALUES (?, 'User Deletion', ?)");
        if (!$logStmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $logStmt->bind_param("is", $userId, $logMessage);
        if (!$logStmt->execute()) {
            throw new Exception("Execute failed: (" . $logStmt->errno . ") " . $logStmt->error);
        }

        // Delete user from specific tables based on user type
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

        // Delete user from users table
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

        $conn->commit();

        error_log("User deletion process completed successfully for User ID: $userId");
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in deleteUser function: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user: ' . $e->getMessage()]);
    }
}

/**
 * Update the status of a user (approve/reject).
 *
 * @param mysqli $conn The database connection.
 * @param int $userId The ID of the user to update.
 * @param string $action The action to perform ('approve' or 'reject').
 * @param array $input The input data, potentially containing 'employee_number'.
 */
function updateUserStatus($conn, $userId, $action, $input) {
    try {
        $conn->begin_transaction();

        error_log("Updating user status: User ID = $userId, Action = $action");

        // Fetch user details
        $verifyStmt = $conn->prepare("SELECT verified, status, user_type, first_name, last_name FROM users WHERE user_id = ?");
        if (!$verifyStmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $verifyStmt->bind_param("i", $userId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        if ($verifyResult->num_rows === 0) {
            throw new Exception('User not found.');
        }
        $user = $verifyResult->fetch_assoc();
        $verifyStmt->close();

        if (!$user['verified']) {
            throw new Exception('User is not verified and cannot be updated.');
        }

        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

        if ($user['status'] === $newStatus) {
            throw new Exception("User is already {$newStatus}.");
        }

        // Update user status
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->bind_param("si", $newStatus, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = ($newStatus === 'approved') ? "User has been successfully approved." : "User has been successfully rejected.";
            error_log("User status updated successfully: $message");

            // Handle based on user type
            if ($newStatus === 'approved') {
                if ($user['user_type'] === 'Student') {
                    // Insert into students table
                    $insertStudentStmt = $conn->prepare("INSERT INTO students (user_id, first_name, last_name) VALUES (?, ?, ?)");
                    if (!$insertStudentStmt) {
                        throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
                    }
                    $insertStudentStmt->bind_param("iss", $userId, $user['first_name'], $user['last_name']);
                    if (!$insertStudentStmt->execute()) {
                        throw new Exception("Failed to insert user into students table: " . $insertStudentStmt->error);
                    }
                    $insertStudentStmt->close();
                } elseif ($user['user_type'] === 'Instructor') {
                    // Insert into instructors table
                    // Use provided employee_number or generate one
                    if (isset($input['employee_number']) && !empty(trim($input['employee_number']))) {
                        $employeeNumber = trim($input['employee_number']);
                    } else {
                        // Auto-generate employee_number
                        $employeeNumber = generateEmployeeNumber($conn);
                    }

                    // Optionally, validate the format of employee_number here

                    $insertInstructorStmt = $conn->prepare("INSERT INTO instructors (user_id, employee_number) VALUES (?, ?)");
                    if (!$insertInstructorStmt) {
                        throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
                    }
                    $insertInstructorStmt->bind_param("is", $userId, $employeeNumber);
                    if (!$insertInstructorStmt->execute()) {
                        throw new Exception("Failed to insert user into instructors table: " . $insertInstructorStmt->error);
                    }
                    $insertInstructorStmt->close();
                }
            }

            // Insert notification
            $notificationMessage = ($newStatus === 'approved') ? "Your account has been approved." : "Your account has been rejected.";
            $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, message, notification_type) VALUES (?, ?, 'account_approval')");
            if (!$notificationStmt) {
                throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            $notificationStmt->bind_param("is", $userId, $notificationMessage);
            if (!$notificationStmt->execute()) {
                throw new Exception("Failed to insert notification: " . $notificationStmt->error);
            }
            $notificationStmt->close();

            // Log the action
            $logMessage = ucfirst($newStatus) . " action taken for User ID $userId.";
            $logStmt = $conn->prepare("INSERT INTO action_logs (user_id, action_type, description) VALUES (?, 'Account Approval', ?)");
            if (!$logStmt) {
                throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            $logStmt->bind_param("is", $userId, $logMessage);
            if (!$logStmt->execute()) {
                throw new Exception("Failed to insert action log: " . $logStmt->error);
            }
            $logStmt->close();

            // Send email notification
            sendEmailNotification($newStatus, $userId, $conn);

            $conn->commit();

            echo json_encode(['status' => 'success', 'message' => $message]);
        } else {
            throw new Exception('No changes were made. User status might already be set to ' . $newStatus . '.');
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in updateUserStatus: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

/**
 * Send email notification to the user regarding account approval/rejection.
 */
function sendEmailNotification($status, $userId, $conn) {
    // Fetch user email and name
    $emailQuery = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
    if (!$emailQuery) {
        error_log("Prepare failed for email query: (" . $conn->errno . ") " . $conn->error);
        return;
    }
    $emailQuery->bind_param("i", $userId);
    $emailQuery->execute();
    $userData = $emailQuery->get_result()->fetch_assoc();
    $emailQuery->close();

    if (!$userData) {
        error_log("User data not found for User ID: $userId");
        return;
    }

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'justinmarlosibonga@gmail.com'; // Replace with your SMTP username
        $mail->Password   = 'mvnhppaolniedhvv'; // Replace with your SMTP password or app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('no-reply@acadmeter.com', 'AcadMeter Admin');
        $mail->addAddress($userData['email'], $userData['first_name']);

        // Content
        $mail->isHTML(false);
        $mail->Subject = ($status === 'approved') ? 'Account Approved' : 'Account Rejected';
        $mail->Body    = ($status === 'approved') ?
            "Dear {$userData['first_name']},\n\nYour account has been approved. Welcome to AcadMeter!\n\nBest regards,\nAcadMeter Team" :
            "Dear {$userData['first_name']},\n\nWe regret to inform you that your account has been rejected.\n\nBest regards,\nAcadMeter Team";

        $mail->send();
        error_log("Email sent to {$userData['email']} regarding account {$status}.");
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

/**
 * Retrieve and return the latest notifications.
 */
function getNotifications($conn) {
    try {
        $stmt = $conn->prepare("SELECT notification_id, user_id, message, notification_type, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 10");
        if (!$stmt) {
            throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($notifications);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fetching notifications: ' . $e->getMessage()]);
    }
}

/**
 * Generates a unique employee number for instructors.
 *
 * @param mysqli $conn The database connection.
 * @return string The generated employee number.
 * @throws Exception If there's an error during generation.
 */
function generateEmployeeNumber($conn) {
    // Fetch the last employee_number
    $stmt = $conn->prepare("SELECT employee_number FROM instructors ORDER BY instructor_id DESC LIMIT 1");
    if (!$stmt) {
        throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $lastEmployeeNumber = $result->fetch_assoc()['employee_number'] ?? 'EMP0000';
    $stmt->close();

    // Extract the numeric part
    $numericPart = (int) substr($lastEmployeeNumber, 3);
    $newNumericPart = $numericPart + 1;
    return 'EMP' . str_pad($newNumericPart, 4, '0', STR_PAD_LEFT);
}
?>
