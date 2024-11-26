<?php
// server/controllers/generate_grade_report.php

ob_start();

require_once __DIR__ . '/../utils/PDFGenerator.php';
require_once __DIR__ . '/../../config/db_connection.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    ob_end_clean();
    header('Location: /AcadMeter/public/login.html');
    exit;
}

if (!isset($_GET['section_id'])) {
    ob_end_clean();
    die('Section ID is required');
}

$sectionId = intval($_GET['section_id']);
$instructorId = $_SESSION['user_id'];

try {
    // Verify section and get section info
    $stmt = $conn->prepare("
        SELECT section_name 
        FROM sections 
        WHERE section_id = ? AND instructor_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param('ii', $sectionId, $instructorId);
    $stmt->execute();
    $result = $stmt->get_result();
    $section = $result->fetch_assoc();

    if (!$section) {
        throw new Exception('Unauthorized access or invalid section');
    }

    $stmt->close();

    // Fetch all students in the section
    $stmt = $conn->prepare("
        SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) as student_name
        FROM students s
        JOIN section_students ss ON s.student_id = ss.student_id
        WHERE ss.section_id = ?
        ORDER BY s.last_name, s.first_name
    ");
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param('i', $sectionId);
    $stmt->execute();
    $studentsResult = $stmt->get_result();
    $students = $studentsResult->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    // Calculate class standing rankings based on general average
    $averageGrades = [];
    foreach ($students as $student) {
        $studentId = $student['student_id'];

        // Fetch grades for all subjects and quarters for this student
        $stmt = $conn->prepare("
            SELECT 
                g.grade
            FROM grades g
            WHERE g.student_id = ? AND g.section_id = ?
        ");
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param('ii', $studentId, $sectionId);
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        $gradesResult = $stmt->get_result();
        $grades = [];
        while ($row = $gradesResult->fetch_assoc()) {
            $grades[] = floatval($row['grade']);
        }

        $stmt->close();

        if (count($grades) > 0) {
            $average = array_sum($grades) / count($grades);
            $averageGrades[$studentId] = round($average, 2);
        } else {
            $averageGrades[$studentId] = 0;
        }
    }

    // Sort students based on average grades to determine ranking
    arsort($averageGrades);
    $ranks = [];
    $rank = 1;
    $prevGrade = null;
    $currentRank = 1;
    foreach ($averageGrades as $studentId => $avgGrade) {
        if ($prevGrade !== null && $avgGrade < $prevGrade) {
            $currentRank = $rank;
        }
        $ranks[$studentId] = $currentRank;
        $prevGrade = $avgGrade;
        $rank++;
    }

    // Initialize PDFGenerator
    $pdfGenerator = new PDFGenerator('P'); // Portrait orientation

    foreach ($students as $student) {
        $studentId = $student['student_id'];
        $studentName = $student['student_name'];
        $studentAverage = isset($averageGrades[$studentId]) ? number_format($averageGrades[$studentId], 2) : '0.00';
        $studentRank = isset($ranks[$studentId]) ? $ranks[$studentId] : 'N/A';

        // Fetch grades for all subjects and quarters for this student
        $stmt = $conn->prepare("
            SELECT 
                sub.subject_name,
                g.quarter,
                g.grade,
                g.remarks
            FROM grades g
            JOIN subjects sub ON g.subject_id = sub.subject_id
            WHERE g.student_id = ? AND g.section_id = ?
            ORDER BY sub.subject_name, g.quarter
        ");
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param('ii', $studentId, $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();

        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $subject = $row['subject_name'];
            $quarter = $row['quarter'];

            if (!isset($grades[$subject])) {
                $grades[$subject] = [
                    'quarters' => [1 => '', 2 => '', 3 => '', 4 => ''],
                    'final_grade' => 0,
                    'remarks' => ''
                ];
            }

            $grades[$subject]['quarters'][$quarter] = number_format(floatval($row['grade']), 2);
            $grades[$subject]['remarks'] = $row['remarks'];
        }

        $stmt->close();

        // Calculate final grades and general average
        $totalFinal = 0;
        $subjectCount = count($grades);
        foreach ($grades as $subject => &$data) {
            $sum = 0;
            $count = 0;
            foreach ($data['quarters'] as $grade) {
                if ($grade !== '') {
                    $sum += floatval($grade);
                    $count++;
                }
            }
            if ($count > 0) {
                $data['final_grade'] = number_format($sum / $count, 2);
                $totalFinal += floatval($data['final_grade']);
            }
        }
        unset($data); // Break reference
        $generalAverage = $subjectCount > 0 ? number_format($totalFinal / $subjectCount, 2) : '0.00';

        // Sort subjects alphabetically
        ksort($grades);

        // Prepare student data
        $studentData = [
            'name' => $studentName,
            'grades' => $grades,
            'general_average' => $generalAverage,
            'rank' => $studentRank
        ];

        // Add report card to PDF
        $pdfGenerator->addPage();
        $pdfGenerator->createReportCard($studentData, $section['section_name'], $studentRank);
    }

    ob_end_clean();
    $pdfGenerator->outputPDF("Grade_Report_{$section['section_name']}.pdf", 'D');

} catch (Exception $e) {
    ob_end_clean();
    die("An error occurred: " . $e->getMessage());
}
?>