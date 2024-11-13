<?php
// C:\xampp\htdocs\AcadMeter\server\controllers\teacher_dashboard_functions.php

session_start();

// Ensure response is in JSON format
header('Content-Type: application/json');

// Validate instructor session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db_connection.php';

/**
 * Function to validate CSRF token
 */
function validate_csrf_token($data) {
    if (!isset($data['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $data['csrf_token']);
}

/**
 * Function to sanitize input data
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validate database connection
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch instructor ID from the session's user ID
$stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($instructor_id);
$fetch_success = $stmt->fetch();
$stmt->close();

if (!$fetch_success || !$instructor_id) {
    echo json_encode(['status' => 'error', 'message' => 'Instructor not found for user_id ' . $user_id]);
    exit;
}

// Retrieve JSON input
$input = json_decode(file_get_contents("php://input"), true);

// Validate CSRF token
if (!validate_csrf_token($input)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

// Determine the action
$action = $input['action'] ?? '';

switch ($action) {
    case 'add_student_to_section':
        add_student_to_section($conn, $instructor_id, $input);
        break;
    case 'input_grade':
        input_grade($conn, $instructor_id, $input);
        break;
    case 'view_performance_metrics':
        view_performance_metrics($conn, $instructor_id);
        break;
    case 'generate_predictions':
        generate_predictions();
        break;
    case 'provide_feedback':
        provide_feedback($conn, $instructor_id, $input);
        break;
    case 'generate_report':
        generate_report();
        break;
    case 'fetch_sections_subjects':
        fetch_sections_and_subjects($conn, $instructor_id);
        break;
    case 'add_subject':
        add_subject($conn, $instructor_id, $input);
        break;
    case 'update_subject':
        update_subject($conn, $instructor_id, $input);
        break;
    case 'delete_subject':
        delete_subject($conn, $instructor_id, $input);
        break;
    case 'upload_csv':
        upload_csv($conn, $instructor_id, $_FILES, $input);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

// Close the database connection
$conn->close();

/**
 * Fetch sections and subjects managed by the instructor
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 */
function fetch_sections_and_subjects($conn, $instructor_id) {
    // Fetch sections
    $stmt = $conn->prepare("SELECT section_id, section_name FROM sections WHERE instructor_id = ?");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database prepare error: ' . $conn->error]);
        return;
    }
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $sections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch subjects assigned to the instructor
    $stmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects WHERE subject_id IN 
                           (SELECT subject_id FROM instructor_subjects WHERE instructor_id = ?)");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database prepare error: ' . $conn->error]);
        return;
    }
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['status' => 'success', 'sections' => $sections, 'subjects' => $subjects]);
}

/**
 * Add a New Subject
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 * @param array $input - Input data
 */
function add_subject($conn, $instructor_id, $input) {
    $subject_name = sanitize_input($input['subject_name'] ?? '');

    if (empty($subject_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject name cannot be empty']);
        return;
    }

    // Check if subject already exists (case-insensitive)
    $stmt_check = $conn->prepare("SELECT 1 FROM subjects WHERE LOWER(subject_name) = LOWER(?)");
    if (!$stmt_check) {
        // Log the error
        error_log("Database error during subject existence check: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_check->bind_param("s", $subject_name);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        echo json_encode(['status' => 'error', 'message' => 'Subject already exists']);
        return;
    }
    $stmt_check->close();

    // Insert new subject
    $stmt_insert = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
    if (!$stmt_insert) {
        // Log the error
        error_log("Database error during subject insertion: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_insert->bind_param("s", $subject_name);

    if ($stmt_insert->execute()) {
        $subject_id = $stmt_insert->insert_id;

        // Optionally, assign the subject to the instructor
        $stmt_assign = $conn->prepare("INSERT INTO instructor_subjects (instructor_id, subject_id) VALUES (?, ?)");
        if ($stmt_assign) {
            $stmt_assign->bind_param("ii", $instructor_id, $subject_id);
            $stmt_assign->execute();
            $stmt_assign->close();
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Subject added successfully',
            'subject_id' => $subject_id,
            'subject_name' => htmlspecialchars($subject_name)
        ]);
    } else {
        // Log the error
        error_log("Database error during subject insertion: " . $stmt_insert->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Failed to add subject: ' . $stmt_insert->error]);
    }

    $stmt_insert->close();
}

/**
 * Update an Existing Subject
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 * @param array $input - Input data
 */
function update_subject($conn, $instructor_id, $input) {
    $subject_id = intval($input['subject_id'] ?? 0);
    $subject_name = sanitize_input($input['subject_name'] ?? '');

    if ($subject_id <= 0 || empty($subject_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid subject ID or subject name']);
        return;
    }

    // Check if subject exists and is managed by the instructor
    $stmt_check = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ? AND subject_id IN 
                                 (SELECT subject_id FROM instructor_subjects WHERE instructor_id = ?)");
    if (!$stmt_check) {
        error_log("Database error during subject existence check: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_check->bind_param("ii", $subject_id, $instructor_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        $stmt_check->close();
        echo json_encode(['status' => 'error', 'message' => 'Subject not found or not managed by you']);
        return;
    }

    $existing_subject = $result->fetch_assoc()['subject_name'];
    $stmt_check->close();

    // Update subject name
    $stmt_update = $conn->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
    if (!$stmt_update) {
        error_log("Database error during subject update: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_update->bind_param("si", $subject_name, $subject_id);

    if ($stmt_update->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Subject updated successfully',
            'subject_name' => htmlspecialchars($subject_name)
        ]);
    } else {
        error_log("Database error during subject update: " . $stmt_update->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Failed to update subject: ' . $stmt_update->error]);
    }

    $stmt_update->close();
}

/**
 * Delete an Existing Subject
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 * @param array $input - Input data
 */
function delete_subject($conn, $instructor_id, $input) {
    $subject_id = intval($input['subject_id'] ?? 0);

    if ($subject_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid subject ID']);
        return;
    }

    // Check if subject exists and is managed by the instructor
    $stmt_check = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = ? AND subject_id IN 
                                 (SELECT subject_id FROM instructor_subjects WHERE instructor_id = ?)");
    if (!$stmt_check) {
        error_log("Database error during subject existence check: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_check->bind_param("ii", $subject_id, $instructor_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        $stmt_check->close();
        echo json_encode(['status' => 'error', 'message' => 'Subject not found or not managed by you']);
        return;
    }

    $subject_name = $result->fetch_assoc()['subject_name'];
    $stmt_check->close();

    // Check if the subject is assigned to any section or has associated grades
    $stmt_assigned = $conn->prepare("SELECT COUNT(*) as count FROM sections WHERE subject_id = ?");
    if (!$stmt_assigned) {
        error_log("Database error during subject assignment check: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_assigned->bind_param("i", $subject_id);
    $stmt_assigned->execute();
    $result_assigned = $stmt_assigned->get_result();
    $count = $result_assigned->fetch_assoc()['count'];
    $stmt_assigned->close();

    if ($count > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete subject. It is currently assigned to one or more sections.']);
        return;
    }

    // Proceed to delete the subject
    $stmt_delete = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
    if (!$stmt_delete) {
        error_log("Database error during subject deletion: " . $conn->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt_delete->bind_param("i", $subject_id);

    if ($stmt_delete->execute()) {
        // Optionally, remove the association from instructor_subjects
        $stmt_remove_assoc = $conn->prepare("DELETE FROM instructor_subjects WHERE subject_id = ?");
        if ($stmt_remove_assoc) {
            $stmt_remove_assoc->bind_param("i", $subject_id);
            $stmt_remove_assoc->execute();
            $stmt_remove_assoc->close();
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Subject deleted successfully',
            'old_subject_name' => htmlspecialchars($subject_name)
        ]);
    } else {
        error_log("Database error during subject deletion: " . $stmt_delete->error, 3, __DIR__ . '/../../logs/error_log.txt');
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete subject: ' . $stmt_delete->error]);
    }

    $stmt_delete->close();
}

/**
 * Add Student to Section
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 * @param array $input - Input data
 */
function add_student_to_section($conn, $instructor_id, $input) {
    // Implementation for adding a student to a section
    // This is a placeholder; implement as per your requirements
    echo json_encode(['status' => 'success', 'message' => 'Student added to section successfully']);
}

/**
 * Input Grade for a Student
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 * @param array $input - Input data
 */
function input_grade($conn, $instructor_id, $input) {
    // Implementation for inputting grades
    // This is a placeholder; implement as per your requirements
    echo json_encode(['status' => 'success', 'message' => 'Grade input successfully.']);
}

/**
 * View Performance Metrics
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 */
function view_performance_metrics($conn, $instructor_id) {
    // Implementation for viewing performance metrics
    // This is a placeholder; implement as per your requirements
    echo json_encode(['status' => 'success', 'metrics' => []]);
}

/**
 * Generate Predictions
 */
function generate_predictions() {
    // Implementation for generating predictions
    // This is a placeholder; implement as per your requirements
    echo json_encode(['status' => 'success', 'predictions' => []]);
}

/**
 * Provide Feedback to Student
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 * @param array $input - Input data
 */
function provide_feedback($conn, $instructor_id, $input) {
    // Implementation for providing feedback to a student
    // This is a placeholder; implement as per your requirements
    echo json_encode(['status' => 'success', 'message' => 'Feedback submitted successfully']);
}

/**
 * Generate Report
 */
function generate_report() {
    // Implementation for generating reports
    // This is a placeholder; implement as per your requirements
    echo json_encode(['status' => 'success', 'report' => []]);
}

/**
 * Handle CSV Upload and Processing
 *
 * @param mysqli $conn - Database connection
 * @param int $instructor_id - ID of the instructor
 * @param array $files - $_FILES array
 * @param array $input - Input data
 */
function upload_csv($conn, $instructor_id, $files, $input) {
    // Implement CSV processing logic here
    // Example steps:
    // 1. Validate the uploaded file
    // 2. Parse the CSV
    // 3. Validate data for each row
    // 4. Insert or update grades in the database

    if (!isset($files['csvFile']) || $files['csvFile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'File upload failed.']);
        return;
    }

    $fileTmpPath = $files['csvFile']['tmp_name'];
    $fileName = $files['csvFile']['name'];
    $fileSize = $files['csvFile']['size'];
    $fileType = $files['csvFile']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    // Check if the uploaded file is a CSV
    if ($fileExtension !== 'csv') {
        echo json_encode(['status' => 'error', 'message' => 'Only CSV files are allowed.']);
        return;
    }

    // Open the CSV file for reading
    if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");
        // Expected headers: Student ID, Quiz Scores, Assignments, Extracurricular, Midterm, Final
        $requiredHeaders = ['Student ID', 'Quiz Scores', 'Assignments', 'Extracurricular', 'Midterm', 'Final'];
        if ($header === FALSE || array_diff($requiredHeaders, $header)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSV format.']);
            fclose($handle);
            return;
        }

        $headerMap = array_flip($header);
        $rowsProcessed = 0;
        $rowsFailed = 0;

        // Begin transaction
        $conn->begin_transaction();

        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $student_id = intval($data[$headerMap['Student ID']] ?? 0);
                $quiz_scores = floatval($data[$headerMap['Quiz Scores']] ?? 0);
                $assignments = floatval($data[$headerMap['Assignments']] ?? 0);
                $extracurricular = floatval($data[$headerMap['Extracurricular']] ?? 0);
                $midterm = floatval($data[$headerMap['Midterm']] ?? 0);
                $final = floatval($data[$headerMap['Final']] ?? 0);

                if ($student_id <= 0) {
                    $rowsFailed++;
                    continue;
                }

                // Calculate final grade (example calculation; adjust as needed)
                $final_grade = ($quiz_scores * 0.3) + ($assignments * 0.2) + ($extracurricular * 0.1) + ($midterm * 0.2) + ($final * 0.2);

                // Check if grade already exists
                $stmt_check = $conn->prepare("SELECT grade_id FROM grades WHERE student_id = ? AND instructor_id = ?");
                if (!$stmt_check) {
                    throw new Exception('Database prepare error: ' . $conn->error);
                }
                $stmt_check->bind_param("ii", $student_id, $instructor_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                if ($result_check->num_rows > 0) {
                    // Update existing grade
                    $grade_id = $result_check->fetch_assoc()['grade_id'];
                    $stmt_update = $conn->prepare("UPDATE grades SET quiz_scores = ?, assignments = ?, extracurricular = ?, midterm = ?, final = ?, final_grade = ? WHERE grade_id = ?");
                    if (!$stmt_update) {
                        throw new Exception('Database prepare error: ' . $conn->error);
                    }
                    $stmt_update->bind_param("ddddddi", $quiz_scores, $assignments, $extracurricular, $midterm, $final, $final_grade, $grade_id);
                    if (!$stmt_update->execute()) {
                        throw new Exception('Failed to update grade: ' . $stmt_update->error);
                    }
                    $stmt_update->close();
                } else {
                    // Insert new grade
                    $stmt_insert = $conn->prepare("INSERT INTO grades (student_id, instructor_id, quiz_scores, assignments, extracurricular, midterm, final, final_grade) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if (!$stmt_insert) {
                        throw new Exception('Database prepare error: ' . $conn->error);
                    }
                    $stmt_insert->bind_param("iidddddi", $student_id, $instructor_id, $quiz_scores, $assignments, $extracurricular, $midterm, $final, $final_grade);
                    if (!$stmt_insert->execute()) {
                        throw new Exception('Failed to insert grade: ' . $stmt_insert->error);
                    }
                    $stmt_insert->close();
                }

                $stmt_check->close();
                $rowsProcessed++;
            }

            fclose($handle);
            // Commit transaction
            $conn->commit();

            echo json_encode([
                'status' => 'success',
                'message' => "CSV uploaded and processed successfully. Rows Processed: $rowsProcessed. Rows Failed: $rowsFailed."
            ]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            // Log the error
            error_log("Error processing CSV: " . $e->getMessage(), 3, __DIR__ . '/../../logs/error_log.txt');
            echo json_encode(['status' => 'error', 'message' => 'Error processing CSV: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to open uploaded CSV file.']);
    }
}
?>
