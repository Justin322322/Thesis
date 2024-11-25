<?php
// server/controllers/generate_at_risk_report.php

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
$atRiskThreshold = 75; // Students below this average are considered at risk

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

// Fetch at-risk students
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
    HAVING average_grade < ?
    ORDER BY average_grade ASC
");
$stmt->bind_param('iid', $sectionId, $instructorId, $atRiskThreshold);
$stmt->execute();
$result = $stmt->get_result();
$atRiskStudents = $result->fetch_all(MYSQLI_ASSOC);

// Generate PDF content
$pdfGenerator = new PDFGenerator();
$content = $pdfGenerator->generateHeader("At-Risk Students Report - {$section['section_name']}");

$content .= "
<div style='margin-bottom: 20px;'>
    <p><strong>Report Date:</strong> " . date('F j, Y') . "</p>
    <p><strong>At-Risk Threshold:</strong> {$atRiskThreshold}%</p>
    <p><strong>Total At-Risk Students:</strong> " . count($atRiskStudents) . "</p>
</div>

<table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
    <tr style='background-color: #f2f2f2;'>
        <th style='border: 1px solid #ddd; padding: 8px;'>Student Name</th>
        <th style='border: 1px solid #ddd; padding: 8px;'>Average Grade</th>
        <th style='border: 1px solid #ddd; padding: 8px;'>Risk Level</th>
        <th style='border: 1px solid #ddd; padding: 8px;'>Areas of Concern</th>
    </tr>";

foreach ($atRiskStudents as $student) {
    $riskLevel = $student['average_grade'] < 60 ? 'High' : 'Moderate';
    $areasOfConcern = [];
    
    if ($student['quiz_score'] < $atRiskThreshold) $areasOfConcern[] = 'Quizzes';
    if ($student['assignment_score'] < $atRiskThreshold) $areasOfConcern[] = 'Assignments';
    if ($student['exam_score'] < $atRiskThreshold) $areasOfConcern[] = 'Exams';
    if ($student['extracurricular_score'] < $atRiskThreshold) $areasOfConcern[] = 'Extracurricular';

    $content .= "
    <tr>
        <td style='border: 1px solid #ddd; padding: 8px;'>{$student['first_name']} {$student['last_name']}</td>
        <td style='border: 1px solid #ddd; padding: 8px;'>" . number_format($student['average_grade'], 2) . "</td>
        <td style='border: 1px solid #ddd; padding: 8px; color: " . ($riskLevel === 'High' ? 'red' : 'orange') . ";'>{$riskLevel}</td>
        <td style='border: 1px solid #ddd; padding: 8px;'>" . implode(', ', $areasOfConcern) . "</td>
    </tr>";
}

$content .= "</table>";

// Generate and download PDF
$pdfGenerator->generateReport($content, "At_Risk_Report_{$section['section_name']}.pdf");