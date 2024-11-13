<?php
// C:\xampp\htdocs\AcadMeter\config\db_connection.php

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acadmeter";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error and exit with a JSON response
    error_log("Connection failed: " . $conn->connect_error, 3, __DIR__ . '/../logs/error_log.txt');
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// Optional: Set character set to UTF-8 for proper encoding
$conn->set_charset("utf8");
?>
