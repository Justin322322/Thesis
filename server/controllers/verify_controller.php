<?php
// Include database connection and PHPMailer
require_once '../../config/db_connection.php';
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$alertClass = "danger";

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Retrieve user data using the verification code
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, user_type FROM users WHERE verification_code = ? AND verified = 0");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        $full_name = $user['first_name'] . ' ' . $user['last_name'];
        $user_type = $user['user_type'];

        // Update user status to verified
        $update_stmt = $conn->prepare("UPDATE users SET verified = 1, verification_timestamp = CURRENT_TIMESTAMP WHERE verification_code = ?");
        $update_stmt->bind_param("s", $code);
        $update_stmt->execute();

        // Log the verification action
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type) VALUES (?, 'Verification')");
        $log_stmt->bind_param("i", $user_id);
        $log_stmt->execute();

        // Create a notification for the admin
        $notification_message = "A new $user_type account has been verified and is pending approval: $full_name";
        $admin_id = 1; // Assuming the admin user_id is 1, adjust if necessary
        $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notification_stmt->bind_param("is", $admin_id, $notification_message);
        $notification_stmt->execute();

        // Email admin with verification notice
        notifyAdmin($notification_message);

        // Personalized success message
        $message = "Congratulations, $full_name! Your email has been successfully verified. 
                    Please wait for admin approval to activate your account. You will receive a notification email 
                    once your account is approved and ready for use. Thank you for joining AcadMeter!";
        $alertClass = "success";
    } else {
        $message = "Invalid or already used verification link.";
    }
} else {
    $message = "No verification code provided.";
}

$conn->close();

// Function to send email notification to admin
function notifyAdmin($message) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'justinmarlosibonga@gmail.com'; // Replace with your email
        $mail->Password = 'mvnhppaolniedhvv'; // Replace with your email password or app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('justinmarlosibonga@gmail.com', 'AcadMeter Admin');
        $mail->addAddress('justinmarlosibonga@gmail.com'); // Replace with the admin's email
        $mail->isHTML(true);
        $mail->Subject = 'New User Verification Notification';
        $mail->Body = "Admin,<br><br>$message<br><br>Thank you,<br>AcadMeter Team";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error sending admin notification: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification - AcadMeter</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/verify_account_styles.css">
</head>
<body>
    <div class="container">
        <div class="verify-account-container">
            <div class="verify-account-header">
                <div class="logo-container">
                    <a href="../../public/login.html">
                        <img src="../../public/assets/img/acadmeter_logo.png" alt="AcadMeter Logo">
                    </a>
                </div>
                <h1>AcadMeter</h1>
            </div>
            <div class="form-container">
                <h2>Account Verification</h2>
                <div class="message-container <?php echo $alertClass; ?>">
                    <?php echo $message; ?>
                </div>
                <div class="form-footer">
                    <a href="../../public/login.html" class="btn-back">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>