<?php
// Include necessary files
require_once '../../config/db_connection.php';
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    $email = isset($_POST['resetEmail']) ? sanitize($_POST['resetEmail']) : '';

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter your email.']);
        exit;
    }

    // Look up the user by email
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        $full_name = $user['first_name'] . ' ' . $user['last_name'];

        // Generate reset token and expiry
        $reset_token = bin2hex(random_bytes(16));
        $reset_token_expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Update the user with reset token and expiry
        $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?");
        $update_stmt->bind_param("ssi", $reset_token, $reset_token_expiry, $user_id);

        if ($update_stmt->execute()) {
            if (sendPasswordResetEmail($email, $reset_token, $full_name)) {
                echo json_encode(['status' => 'success', 'message' => 'Password reset link has been sent to your email.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send reset email. Please try again later.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update reset token.']);
        }
        $update_stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email address not found.']);
    }
    $stmt->close();
    exit;
}

// Send password reset email using PHPMailer
function sendPasswordResetEmail($email, $reset_token, $full_name) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = ''; // Replace with your email
        $mail->Password = ''; // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('no-reply@acadmeter.com', 'AcadMeter');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'AcadMeter Password Reset Request';

        $resetLink = "http://localhost/AcadMeter/public/reset_password.html?token=$reset_token";
        $mail->Body = "Dear $full_name,<br><br>You requested a password reset. Please click the link below to reset your password:<br><br>
                      <a href='$resetLink'>Reset Password</a><br><br>This link will expire in 1 hour.<br><br>Thank you,<br>The AcadMeter Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
