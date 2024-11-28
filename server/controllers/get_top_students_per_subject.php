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

// Fetch top performing students for each subject
if ($section_id > 0) {
    $stmt = $conn->prepare("
        WITH RankedGrades AS (
            SELECT 
                g.student_id,
                g.subject_id,
                g.section_id,
                g.grade,
                s.first_name,
                s.last_name,
                sj.subject_name,
                sec.section_name,
                DENSE_RANK() OVER (PARTITION BY g.subject_id ORDER BY g.grade DESC) as rank
            FROM grades g
            JOIN students s ON g.student_id = s.student_id
            JOIN subjects sj ON g.subject_id = sj.subject_id
            JOIN sections sec ON g.section_id = sec.section_id
            WHERE g.section_id = ?
        )
        SELECT DISTINCT
            student_id,
            CONCAT(first_name, ' ', last_name) as student_name,
            subject_name,
            section_name,
            grade
        FROM RankedGrades
        WHERE rank = 1
        ORDER BY subject_name, grade DESC
    ");
    if (!$stmt) {
        error_log('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('i', $section_id);
} else {
    $stmt = $conn->prepare("
        WITH RankedGrades AS (
            SELECT 
                g.student_id,
                g.subject_id,
                g.section_id,
                g.grade,
                s.first_name,
                s.last_name,
                sj.subject_name,
                sec.section_name,
                DENSE_RANK() OVER (PARTITION BY g.subject_id ORDER BY g.grade DESC) as rank
            FROM grades g
            JOIN students s ON g.student_id = s.student_id
            JOIN subjects sj ON g.subject_id = sj.subject_id
            JOIN sections sec ON g.section_id = sec.section_id
            WHERE sec.instructor_id = ?
        )
        SELECT DISTINCT
            student_id,
            CONCAT(first_name, ' ', last_name) as student_name,
            subject_name,
            section_name,
            grade
        FROM RankedGrades
        WHERE rank = 1
        ORDER BY subject_name, grade DESC
    ");
    if (!$stmt) {
        error_log('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('i', $instructor_id);
}

$stmt->execute();
$result = $stmt->get_result();

$awardData = [];
while ($row = $result->fetch_assoc()) {
    $awardData[] = [
        'subject' => $row['subject_name'],
        'student_id' => $row['student_id'],
        'student_name' => $row['student_name'],
        'section_name' => $row['section_name'],
        'top_grade' => round($row['grade'], 2)
    ];
}

$stmt->close();
$conn->close();

echo json_encode($awardData);
?>
