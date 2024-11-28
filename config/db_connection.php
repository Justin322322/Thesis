<?php
// C:\xampp\htdocs\AcadMeter\config\db_connection.php

// Enable error reporting for debugging (Remove or comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acadmeter";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}


// Set charset to utf8mb4
if (!$conn->set_charset("utf8mb4")) {
    error_log('Error loading character set utf8mb4: ' . $conn->error);
    die(json_encode(['status' => 'error', 'message' => 'Error loading character set utf8mb4: ' . $conn->error]));
}
?>
