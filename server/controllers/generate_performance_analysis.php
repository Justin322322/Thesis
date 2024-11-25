<?php
// server/controllers/generate_performance_analysis.php

require_once __DIR__ . '/../utils/PDFGenerator.php';
require_once __DIR__ . '/../../config/db_connection.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.php');
    exit;
}

if (!isset($_GET['section_id'])) {
    die('Section ID is required');
}

$sectionId = intval($_GET['section_id']);
$instructorId = $_SESSION['user_id'];

// Verify section belongs to instructor
$stmt = $conn->prepare("
    SELECT section_name 
    FROM sections 
    WHERE section_id = ? AND instructor_id = ?
");
$stmt->bind_param('ii', $sectionId, $instructorId);
$stmt->execute();
$result = $stmt->get_result();
$section = $result->fetch_assoc();

if (!$section) {
    die('Unauthorized access or invalid section');
}

// Fetch performance data
$stmt = $conn->prepare("
    SELECT 
        s.first_name,
        s.last_name,
        qp.quiz_score,
        qp.assignment_score,
        qp.exam_score,
        qp.extracurricular_score,
        qp.total_score as average_grade
    FROM students s
    JOIN quarterly_performance qp ON s.student_id = qp.student_id
    JOIN section_students ss ON s.student_id = ss.student_id
    WHERE ss.section_id = ? AND qp.instructor_id = ?
    ORDER BY average_grade DESC
");
$stmt->bind_param('ii', $sectionId, $instructorId);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Generate PDF content
$pdfGenerator = new PDFGenerator();
$content = $pdfGenerator->generateHeader("Performance Analysis - {$section['section_name']}");

$content .= "
<table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
    <tr style='background-color: #f2f2f2;'>
        <th style='border: 1px solid #ddd; padding: 8px;'>Student Name</th>
        <th style='border: 1px solid #ddd; padding: 8px;'>Quiz</th>
        <th style='border: 1px solid #ddd; padding: 8px;'>Assignment</th>
        <th style='border: 1px solid #ddd; padding: 8px;'>Exam</th>
        <th style='border: 1px solid #ddd; padding: 8px;'>Extra</th>
        <th style='border: 1px solid #ddd; padding: 8px;'>Average</th>
    </tr>";

foreach ($students as $student) {
    $content .= "
    <tr>
        <td style='border: 1px solid #ddd; padding: 8px;'>{$student['first_name']} {$student['last_name']}</td>
        <td style='border: 1px solid #ddd; padding: 8px;'>{$student['quiz_score']}</td>
        <td style='border: 1px solid #ddd; padding: 8px;'>{$student['assignment_score']}</td>
        <td style='border: 1px solid #ddd; padding: 8px;'>{$student['exam_score']}</td>
        <td style='border: 1px solid #ddd; padding: 8px;'>{$student['extracurricular_score']}</td>
        <td style='border: 1px solid #ddd; padding: 8px;'>" . number_format($student['average_grade'], 2) . "</td>
    </tr>";
}

$content .= "</table>";

// Generate and download PDF
$pdfGenerator->generateReport($content, "Performance_Analysis_{$section['section_name']}.pdf");