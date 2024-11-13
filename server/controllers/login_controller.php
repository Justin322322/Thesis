<?php
// server/controllers/login_controller.php

// Include necessary files
require_once '../../config/db_connection.php';

// Check if the sanitize function exists before defining it
if (!function_exists('sanitize')) {
    // Sanitize input function
    function sanitize($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture form data
    $email_or_username = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate inputs
    if (empty($email_or_username) || empty($password)) {
        header("Location: ../../public/login.html?error=" . urlencode("All fields are required.") . "&email=" . urlencode($email_or_username));
        exit;
    }

    // Prepare SQL to find user by email or username
    $stmt = $conn->prepare("SELECT user_id, username, password, verified, status, user_type FROM users WHERE (email = ? OR username = ?)");
    $stmt->bind_param("ss", $email_or_username, $email_or_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if password is correct
        if (password_verify($password, $user['password'])) {
            if ($user['verified'] == 1) {
                if ($user['status'] === 'approved') {
                    session_start();
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];

                    // Redirect to appropriate dashboard based on user type
                    if ($user['user_type'] === 'Admin') {
                        header("Location: ../../public/admin_dashboard.php");
                    } elseif ($user['user_type'] === 'Instructor') {
                        header("Location: ../../public/teacher_dashboard.php");
                    } else {
                        header("Location: ../../public/student_dashboard.php");
                    }
                    exit;
                } else {
                    header("Location: ../../public/login.html?error=" . urlencode("Your account is awaiting admin approval.") . "&email=" . urlencode($email_or_username));
                    exit;
                }
            } else {
                header("Location: ../../public/login.html?error=" . urlencode("Please verify your email before logging in.") . "&email=" . urlencode($email_or_username));
                exit;
            }
        } else {
            header("Location: ../../public/login.html?error=" . urlencode("Incorrect password.") . "&email=" . urlencode($email_or_username));
            exit;
        }
    } else {
        header("Location: ../../public/login.html?error=" . urlencode("Invalid username or email.") . "&email=" . urlencode($email_or_username));
        exit;
    }
}
$conn->close();
?>
