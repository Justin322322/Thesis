<?php
require_once '../../config/db_connection.php';
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = isset($_POST['newPassword']) ? sanitize($_POST['newPassword']) : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? sanitize($_POST['confirmPassword']) : '';
    $token = isset($_POST['token']) ? sanitize($_POST['token']) : '';

    if (empty($newPassword) || empty($confirmPassword) || empty($token)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Check if passwords match
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit;
    }

    // Password validation
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $newPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters, with 1 uppercase letter and 1 number.']);
        exit;
    }

    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT user_id, reset_token_expiry FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (new DateTime() > new DateTime($user['reset_token_expiry'])) {
            echo json_encode(['status' => 'error', 'message' => 'Reset token has expired.']);
            exit;
        }

        // Hash the new password and update it
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $user['user_id']);

        if ($updateStmt->execute()) {
            // Optionally send a notification email to the user
            $email = 'user@example.com'; // Fetch the user email from your database based on `user_id`
            if (sendNotificationEmail($email)) {
                echo json_encode(['status' => 'success', 'message' => 'Password successfully changed.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Password updated, but notification email failed.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
        }
        $updateStmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired reset token.']);
    }
    $stmt->close();
    exit;
}

function sendNotificationEmail($email) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = ''; // Replace with your email
        $mail->Password = ''; // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('justinmarlosibonga@gmail.com', 'AcadMeter');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your AcadMeter Password Was Changed';

        $mail->Body = "Your password has been successfully changed. If you did not make this change, please contact support.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
