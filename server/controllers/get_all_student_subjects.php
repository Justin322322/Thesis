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

$query = "
    SELECT 
        s.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        sj.subject_name,
        AVG(g.grade) AS average_grade
    FROM grades g
    JOIN students s ON g.student_id = s.student_id
    JOIN subjects sj ON g.subject_id = sj.subject_id
";

if ($section_id === 0) {
    $query .= " WHERE sec.instructor_id = ?";
    $types = 'i';
    $params = [$instructor_id];
} else {
    $query .= " WHERE g.section_id = ?";
    $types = 'i';
    $params = [$section_id];
}

$query .= " GROUP BY s.student_id, sj.subject_id, sj.subject_name ORDER BY s.student_id, average_grade DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();

$allSubjectData = [];
while ($row = $result->fetch_assoc()) {
    $student_id_current = $row['student_id'];
    if (!isset($allSubjectData[$student_id_current])) {
        $allSubjectData[$student_id_current] = [
            'student_id' => $student_id_current,
            'student_name' => $row['student_name'],
            'subjects' => []
        ];
    }
    $allSubjectData[$student_id_current]['subjects'][] = [
        'subject' => $row['subject_name'],
        'grade' => round($row['average_grade'], 2)
    ];
}

$stmt->close();
$conn->close();

// Reindex the array for JSON encoding
$allSubjectData = array_values($allSubjectData);

echo json_encode($allSubjectData);
?>