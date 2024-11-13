<?php
// C:\xampp\htdocs\AcadMeter\server\controllers\predictive_analytics_controller.php

session_start();

// Ensure response is in JSON format
header('Content-Type: application/json');

// Validate instructor session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db_connection.php';

/**
 * Function to fetch students data from the database
 * Modify the query based on your database schema
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 * @return array - Array of students with required features
 */
function fetch_students_data($conn, $instructor_id) {
    $students = [];
    
    // Example query: Adjust according to your database structure
    $query = "
        SELECT 
            s.student_id,
            s.name,
            g.current_grade,
            g.attendance,
            g.participation,
            g.assignments_completed,
            g.exams_score
        FROM students s
        INNER JOIN grades g ON s.student_id = g.student_id
        WHERE g.instructor_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['error' => 'Database prepare error: ' . $conn->error];
    }
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'student_id' => $row['student_id'],
            'name' => $row['name'],
            'current_grade' => $row['current_grade'],
            'attendance' => $row['attendance'],
            'participation' => $row['participation'],
            'assignments_completed' => $row['assignments_completed'],
            'exams_score' => $row['exams_score']
        ];
    }
    
    $stmt->close();
    return $students;
}

// Fetch instructor ID from the session's user ID
$user_id = $_SESSION['user_id'];

// Fetch instructor ID
$stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($instructor_id);
$fetch_success = $stmt->fetch();
$stmt->close();

if (!$fetch_success || !$instructor_id) {
    echo json_encode(['status' => 'error', 'message' => 'Instructor not found for user_id ' . $user_id]);
    exit;
}

// Fetch students data
$students_data = fetch_students_data($conn, $instructor_id);
if (isset($students_data['error'])) {
    echo json_encode(['status' => 'error', 'message' => $students_data['error']]);
    exit;
}

// Prepare data for the ML model
$students_for_prediction = [];
foreach ($students_data as $student) {
    $students_for_prediction[] = [
        'student_id' => $student['student_id'],
        'name' => $student['name'],
        'current_grade' => $student['current_grade'],
        'attendance' => $student['attendance'],
        'participation' => $student['participation'],
        'assignments_completed' => $student['assignments_completed'],
        'exams_score' => $student['exams_score']
    ];
}

// Send data to the Python ML API
$api_url = 'http://localhost:5000/predict'; // Ensure this matches your Flask API URL

$payload = json_encode(['students' => $students_for_prediction]);

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to communicate with the prediction service.']);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Decode the response
$response_data = json_decode($response, true);

if ($http_code !== 200 || $response_data['status'] !== 'success') {
    $error_message = $response_data['message'] ?? 'Unknown error from prediction service.';
    echo json_encode(['status' => 'error', 'message' => 'Prediction service error: ' . $error_message]);
    exit;
}

// Fetch at-risk students
$at_risk_students = $response_data['at_risk_students'];

// Optionally, you can store these predictions in your database for future reference
// Example:
// foreach ($at_risk_students as $student) {
//     // Update the grades table or a separate at_risk_students table
// }

echo json_encode(['status' => 'success', 'at_risk_students' => $at_risk_students]);
?>
