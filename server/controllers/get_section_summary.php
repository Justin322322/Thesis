<?php
// get_section_summary.php

header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode([]);
    exit;
}

// Get the instructor_id and section_id from the request
$instructor_id = $_SESSION['user_id'];
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';

if ($section_id === 0) {
    $stmt = $conn->prepare("
        SELECT 
            s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            (SELECT AVG(grade) FROM grades WHERE student_id = s.student_id) AS final_grade
        FROM students s
        JOIN grades g ON s.student_id = g.student_id
        JOIN sections sec ON g.section_id = sec.section_id
        WHERE sec.instructor_id = ?
        GROUP BY s.student_id
    ");
    if (!$stmt) {
        error_log('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('i', $instructor_id);
} else {
    // Verify that the section belongs to the instructor
    $stmt = $conn->prepare("
        SELECT section_id
        FROM sections
        WHERE section_id = ? AND instructor_id = ?
    ");
    if (!$stmt) {
        error_log('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('ii', $section_id, $instructor_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        echo json_encode([]);
        exit;
    }
    $stmt->close();

    // Fetch grade distribution and student names for the specific section
    $stmt = $conn->prepare("
        SELECT 
            s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            (SELECT AVG(grade) FROM grades WHERE student_id = s.student_id AND section_id = ?) AS final_grade
        FROM students s
        JOIN grades g ON s.student_id = g.student_id
        WHERE g.section_id = ?
        GROUP BY s.student_id
    ");
    if (!$stmt) {
        error_log('Prepare failed: (' . $conn->errno . ') ' . $conn->error);
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param('ii', $section_id, $section_id);
}

$stmt->execute();
$result = $stmt->get_result();

$categoryData = [
    'Outstanding' => ['count' => 0, 'students' => []],
    'Very Satisfactory' => ['count' => 0, 'students' => []],
    'Satisfactory' => ['count' => 0, 'students' => []],
    'Fair' => ['count' => 0, 'students' => []],
    'Needs Improvement' => ['count' => 0, 'students' => []]
];

while ($row = $result->fetch_assoc()) {
    $grade = round($row['final_grade'], 2);
    $category = '';

    if ($grade >= 90) {
        $category = 'Outstanding';
    } elseif ($grade >= 80) {
        $category = 'Very Satisfactory';
    } elseif ($grade >= 70) {
        $category = 'Satisfactory';
    } elseif ($grade >= 60) {
        $category = 'Fair';
    } else {
        $category = 'Needs Improvement';
    }

    $categoryData[$category]['count']++;
    $categoryData[$category]['students'][] = [
        'name' => $row['student_name'],
        'grade' => $grade
    ];
    $categoryData[$category]['total_grade'] = ($categoryData[$category]['total_grade'] ?? 0) + $grade;
}

$sectionSummaryData = [];
foreach ($categoryData as $category => $data) {
    if ($data['count'] > 0) {
        $avg_grade = round($data['total_grade'] / $data['count'], 2);

        // Get unique students for this category
        $unique_students = array_values(array_unique(array_map(function($student) {
            return $student['name'];
        }, $data['students'])));

        $sectionSummaryData[] = [
            'grade_category' => $category,
            'percentage' => count($unique_students), // Use count of unique students
            'average_grade' => $avg_grade,
            'students' => array_values(array_reduce($data['students'], function($carry, $student) {
                if (!isset($carry[$student['name']])) {
                    $carry[$student['name']] = $student;
                }
                return $carry;
            }, []))
        ];
    }
}

echo json_encode($sectionSummaryData);
?>
