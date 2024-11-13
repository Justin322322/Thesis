<?php
// Include necessary files and initialize PHPMailer
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../config/db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sanitize input to prevent SQL injection
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture form data and sanitize inputs
    $user_type = isset($_POST['user_type']) ? sanitize($_POST['user_type']) : '';
    $first_name = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $sex = isset($_POST['sex']) ? sanitize($_POST['sex']) : '';
    $dob = isset($_POST['dob']) ? sanitize($_POST['dob']) : '';
    $verification_code = bin2hex(random_bytes(16)); // Generate verification code

    // Combine first and last name for full name
    $full_name = $first_name . ' ' . $last_name;

    // Validation for form fields
    if (empty($user_type) || empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Validate that passwords match
    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit;
    }

    // Validate password strength
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long, with 1 uppercase letter and 1 number.']);
        exit;
    }

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check for duplicate email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email is already registered.']);
        exit;
    }

    // Insert user into users table with 'pending' status
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, password, email, user_type, status, verification_code, verified, dob, sex) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, 0, ?, ?)");
    $stmt->bind_param("sssssssss", $first_name, $last_name, $username, $hashed_password, $email, $user_type, $verification_code, $dob, $sex);
    if ($stmt->execute()) {
        // Send verification email
        if (sendVerificationEmail($email, $verification_code, $full_name)) {
            echo json_encode(['status' => 'success', 'message' => 'Registration successful. Please verify your email.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Registration successful, but verification email could not be sent.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
    }
    $stmt->close();
    $conn->close();
}

// Send verification email using PHPMailer with personalization
function sendVerificationEmail($email, $verification_code, $full_name) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'justinmarlosibonga@gmail.com'; // Replace with your email
        $mail->Password = 'mvnhppaolniedhvv'; // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your_email@gmail.com', 'AcadMeter');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification for AcadMeter Registration';

        // Include the full name in the email message
        $verificationLink = "http://localhost/AcadMeter/server/controllers/verify_controller.php?code=$verification_code";
        $mail->Body = "Dear $full_name,<br><br>Thank you for registering with AcadMeter. Please click the link below to verify your email address:<br><br>
                      <a href='$verificationLink'>Verify Email</a><br><br>Thank you!<br>The AcadMeter Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
