
<?php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../../config/db_connection.php';

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
$instructor_id = $_SESSION['user_id'];

if ($section_id === 0) {
    // If 'All Sections' is selected, fetch all subjects taught by the instructor
    $stmt = $conn->prepare("
        SELECT DISTINCT sj.subject_id, sj.subject_name
        FROM grades g
        JOIN subjects sj ON g.subject_id = sj.subject_id
        JOIN sections sec ON g.section_id = sec.section_id
        WHERE sec.instructor_id = ?
        ORDER BY sj.subject_name ASC
    ");
    if (!$stmt) {
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('i', $instructor_id);
} else {
    // Fetch subjects specific to the selected section
    $stmt = $conn->prepare("
        SELECT sj.subject_id, sj.subject_name
        FROM grades g
        JOIN subjects sj ON g.subject_id = sj.subject_id
        WHERE g.section_id = ?
        ORDER BY sj.subject_name ASC
    ");
    if (!$stmt) {
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('i', $section_id);
}

$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = [
        'subject_id' => $row['subject_id'],
        'subject_name' => $row['subject_name']
    ];
}

$stmt->close();
$conn->close();

echo json_encode($subjects);
?>