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

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if ($student_id === 0 && isset($_GET['student_id']) && $_GET['student_id'] === 'all') {
    // Redirect to get_all_student_subjects.php to handle "all" case
    require_once __DIR__ . '/get_all_student_subjects.php';
    exit;
}

if ($student_id === 0) {
    echo json_encode([]);
    exit;
}

$query = "
    SELECT 
        s.subject_name,
        AVG(g.grade) as average_grade
    FROM grades g
    JOIN subjects s ON g.subject_id = s.subject_id
    WHERE g.student_id = ?
";

$types = 'i';
$params = [$student_id];

if ($section_id > 0) {
    $query .= " AND g.section_id = ?";
    $types .= 'i';
    $params[] = $section_id;
}

$query .= " GROUP BY s.subject_id, s.subject_name ORDER BY average_grade DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();

$subjectData = [];
while ($row = $result->fetch_assoc()) {
    $subjectData[] = [
        'subject' => $row['subject_name'],
        'grade' => round($row['average_grade'], 2)
    ];
}

$stmt->close();
$conn->close();

echo json_encode($subjectData);
?>