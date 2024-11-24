<?php
// C:\xampp\htdocs\AcadMeter\config\db_connection.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acadmeter";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Set charset to utf8mb4
if (!$conn->set_charset("utf8mb4")) {
    die(json_encode(['status' => 'error', 'message' => 'Error loading character set utf8mb4: ' . $conn->error]));
}
?>
