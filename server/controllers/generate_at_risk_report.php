<?php
// server/controllers/generate_at_risk_report.php

require_once __DIR__ . '/../utils/PDFGenerator.php';
require_once __DIR__ . '/../../config/db_connection.php';

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Verify user authentication and role
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

// Fetch at-risk students
$query = "
    SELECT 
        s.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS name,
        AVG(g.grade) AS average_final_grade
    FROM students s
    JOIN section_students ss ON s.student_id = ss.student_id
    JOIN grades g ON s.student_id = g.student_id AND g.section_id = ss.section_id
    WHERE ss.section_id = ? 
    GROUP BY s.student_id, s.first_name, s.last_name
    HAVING AVG(g.grade) < ?
    ORDER BY AVG(g.grade) ASC
";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("ii", $sectionId, $atRiskThreshold);

if (!$stmt->execute()) {
    die("Query execution failed: " . $stmt->error);
}

$result = $stmt->get_result();

$atRiskStudents = [];
$averageGrades = [];

while ($row = $result->fetch_assoc()) {
    $studentId = $row['student_id'];
    $studentName = $row['name'];
    $averageGrade = round(floatval($row['average_final_grade']), 2);

    // Determine risk level
    $riskLevel = getRiskLevel($averageGrade);

    // Fetch areas of concern (subjects below threshold)
    $stmtAreas = $conn->prepare("
        SELECT sub.subject_name
        FROM grades g
        JOIN subjects sub ON g.subject_id = sub.subject_id
        WHERE g.student_id = ? 
        AND g.section_id = ?
        AND g.grade < ?
    ");
    if ($stmtAreas) {
        $stmtAreas->bind_param("iii", $studentId, $sectionId, $atRiskThreshold);
        if ($stmtAreas->execute()) {
            $resultAreas = $stmtAreas->get_result();
            $areas = [];
            while ($areaRow = $resultAreas->fetch_assoc()) {
                $areas[] = $areaRow['subject_name'];
            }
            $stmtAreas->close();
        } else {
            $stmtAreas->close();
            $areas = [];
        }
    } else {
        $areas = [];
    }

    // Assign remarks based on average grade
    $remarks = getRemarks($averageGrade);

    $atRiskStudents[] = [
        'name' => $studentName,
        'average_final_grade' => $averageGrade,
        'risk_level' => $riskLevel,
        'remarks' => $remarks,
        'areas_of_concern' => $areas
    ];

    $averageGrades[$studentName] = $averageGrade;
}

$stmt->close();

// Check if at-risk students are available
if (empty($atRiskStudents)) {
    die('No at-risk students found for this section.');
}

// Initialize PDFGenerator
$pdfGenerator = new PDFGenerator('P'); // Portrait orientation
$pdfGenerator->addPage();

// Add report title
$pdfGenerator->setFont('helvetica', 'B', 16);
$pdfGenerator->addCell(0, 10, "At-Risk Students Report - {$section['section_name']}", 0, 1, 'C');
$pdfGenerator->ln(5);

// Add description
$pdfGenerator->setFont('helvetica', '', 12);
$description = 'This report identifies students who are at risk based on their average grades. It provides insights into their risk levels and highlights specific areas of concern to inform targeted interventions.';
$pdfGenerator->addMultiCellCustom(0, 6, $description, 0, 'L');
$pdfGenerator->ln(5);

// Dynamic Column Widths
$headers = ['Student Name', 'Average Grade', 'Risk Level', 'Remarks', 'Areas of Concern'];
$columnWidths = $pdfGenerator->calculateColumnWidths($headers);

// Table Headers
$pdfGenerator->addTableHeader($headers, $columnWidths);

// Table Rows
foreach ($atRiskStudents as $student) {
    $studentName = htmlspecialchars($student['name']);
    $averageGrade = number_format($student['average_final_grade'], 2);
    $riskLevel = htmlspecialchars($student['risk_level']);
    $remarks = htmlspecialchars($student['remarks']);
    $areasOfConcern = htmlspecialchars(implode(', ', $student['areas_of_concern']));

    $rowData = [
        $studentName,
        $averageGrade,
        $riskLevel,
        $remarks,
        $areasOfConcern
    ];

    $pdfGenerator->addTableRow($rowData, $columnWidths);
}

$pdfGenerator->ln(5);

// Grading Scale
$pdfGenerator->setFont('helvetica', 'B', 12);
$pdfGenerator->addCell(0, 8, "Grading Scale:", 0, 1, 'L');
$pdfGenerator->setFont('helvetica', '', 10);
$gradingScale = [
    'Outstanding (90-100) - Passed',
    'Very Satisfactory (85-89) - Passed',
    'Satisfactory (80-84) - Passed',
    'Fairly Satisfactory (75-79) - Passed',
    'Did Not Meet Expectations (Below 75) - Failed',
];
foreach ($gradingScale as $scale) {
    $pdfGenerator->addMultiCellCustom(0, 6, $scale, 0, 'L');
}

// Add graph
if (!empty($averageGrades)) {
    $pdfGenerator->addGraph($averageGrades, 'Average Grades of At-Risk Students');
}

// Output PDF
$pdfGenerator->outputPDF("At_Risk_Report_{$section['section_name']}.pdf", 'D');

// Helper Functions

/**
 * Determines the risk level based on the average grade.
 *
 * @param float $averageGrade The average grade of the student.
 * @return string The risk level ('High', 'Moderate', 'Low').
 */
function getRiskLevel($averageGrade) {
    if ($averageGrade < 60) {
        return 'High';
    } elseif ($averageGrade < 75) {
        return 'Moderate';
    } else {
        return 'Low';
    }
}

/**
 * Determines the remarks based on the average grade.
 *
 * @param float $averageGrade The average grade of the student.
 * @return string The corresponding remark.
 */
function getRemarks($averageGrade) {
    if ($averageGrade >= 90 && $averageGrade <= 100) {
        return 'Outstanding';
    } elseif ($averageGrade >= 85 && $averageGrade <= 89) {
        return 'Very Satisfactory';
    } elseif ($averageGrade >= 80 && $averageGrade <= 84) {
        return 'Satisfactory';
    } elseif ($averageGrade >= 75 && $averageGrade <= 79) {
        return 'Fairly Satisfactory';
    } else {
        return 'Did Not Meet Expectations';
    }
}
?>
