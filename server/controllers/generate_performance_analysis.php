<?php
// server/controllers/generate_performance_analysis.php

require_once __DIR__ . '/../utils/PDFGenerator.php';
require_once __DIR__ . '/../../config/db_connection.php';

session_start();

// Verify user authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.html');
    exit;
}

if (!isset($_GET['section_id'])) {
    die('Section ID is required');
}

$sectionId = intval($_GET['section_id']);
$instructorId = $_SESSION['user_id'];

// Verify that the section belongs to the instructor
$stmt = $conn->prepare("
    SELECT section_name 
    FROM sections 
    WHERE section_id = ? AND instructor_id = ?
");
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->bind_param('ii', $sectionId, $instructorId);

if (!$stmt->execute()) {
    die("Query execution failed: " . $stmt->error);
}

$result = $stmt->get_result();
$section = $result->fetch_assoc();

if (!$section) {
    die('Unauthorized access or invalid section');
}

$stmt->close();

// Fetch performance data
$stmt = $conn->prepare("
    SELECT 
        s.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS full_name,
        qp.quiz_score,
        qp.assignment_score,
        qp.exam_score,
        qp.extracurricular_score,
        qp.total_score AS average_grade
    FROM students s
    JOIN quarterly_performance qp ON s.student_id = qp.student_id
    JOIN section_students ss ON s.student_id = ss.student_id
    WHERE ss.section_id = ?
    ORDER BY average_grade DESC
");
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->bind_param('i', $sectionId);

if (!$stmt->execute()) {
    die("Query execution failed: " . $stmt->error);
}

$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Check if performance data is available
if (empty($students)) {
    die('No performance data available for this section.');
}

// Calculate class standing rankings
$rank = 1;
$prevGrade = null;
$currentRank = 1;
$ranks = [];
foreach ($students as $student) {
    if ($prevGrade !== null && $student['average_grade'] < $prevGrade) {
        $currentRank = $rank;
    }
    $ranks[$student['student_id']] = $currentRank;
    $prevGrade = $student['average_grade'];
    $rank++;
}

// Prepare data for the graph (Average Grades by Student)
$graphData = [];
foreach ($students as $student) {
    $graphData[$student['full_name']] = round(floatval($student['average_grade']), 2);
}

// Initialize PDFGenerator
$pdfGenerator = new PDFGenerator('P'); // Portrait orientation
$pdfGenerator->addPage();

// Add report title
$pdfGenerator->setFont('helvetica', 'B', 16);
$pdfGenerator->addCell(0, 10, "Performance Analysis - {$section['section_name']}", 0, 1, 'C');
$pdfGenerator->ln(5);

// Add description
$pdfGenerator->setFont('helvetica', '', 12);
$description = 'This report analyzes the quarterly performance of students, including scores from quizzes, assignments, exams, and extracurricular activities. The average grade provides an overall assessment of each student\'s performance and their standing within the class.';
$pdfGenerator->addMultiCellCustom(0, 6, $description, 0, 'L');
$pdfGenerator->ln(5);

// Dynamic Column Widths
$headers = ['Student Name', 'Quiz Score', 'Assignment Score', 'Exam Score', 'Extra Activities', 'Average Grade', 'Rank'];
$columnWidths = $pdfGenerator->calculateColumnWidths($headers);

// Table Headers
$pdfGenerator->addTableHeader($headers, $columnWidths);

// Table Rows
foreach ($students as $student) {
    $fullName = htmlspecialchars($student['full_name']);
    $quizScore = number_format(floatval($student['quiz_score']), 2);
    $assignmentScore = number_format(floatval($student['assignment_score']), 2);
    $examScore = number_format(floatval($student['exam_score']), 2);
    $extraScore = number_format(floatval($student['extracurricular_score']), 2);
    $averageGrade = number_format(floatval($student['average_grade']), 2);
    $studentRank = isset($ranks[$student['student_id']]) ? $ranks[$student['student_id']] : 'N/A';

    $rowData = [
        $fullName,
        $quizScore,
        $assignmentScore,
        $examScore,
        $extraScore,
        $averageGrade,
        $studentRank
    ];

    $pdfGenerator->addTableRow($rowData, $columnWidths);
}

$pdfGenerator->ln(5);

// Add graph
if (!empty($graphData)) {
    $pdfGenerator->addGraph($graphData, 'Average Grades by Student');
}

// Output PDF
$pdfGenerator->outputPDF("Performance_Analysis_{$section['section_name']}.pdf", 'D');
?>
