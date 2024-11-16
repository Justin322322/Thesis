<?php
$current_page = 'grade_management';

// Check if the user is authenticated and has the Instructor role
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.php');
    exit;
}

require_once __DIR__ . '/../../config/db_connection.php';

/**
 * Fetch all sections with their assigned subject and students
 */
function fetchSections($conn) {
    $sections = [];
    $stmt = $conn->prepare("
        SELECT s.section_id, s.section_name, sub.subject_name, sub.subject_id
        FROM sections s
        LEFT JOIN subjects sub ON s.subject_id = sub.subject_id
        ORDER BY s.section_name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Fetch assigned students for each section
        $stmt_students = $conn->prepare("
            SELECT stu.student_id, stu.first_name, stu.last_name
            FROM students stu
            JOIN section_students ss ON stu.student_id = ss.student_id
            WHERE ss.section_id = ?
            ORDER BY stu.first_name ASC, stu.last_name ASC
        ");
        $stmt_students->bind_param("i", $row['section_id']);
        $stmt_students->execute();
        $result_students = $stmt_students->get_result();
        $students = $result_students->fetch_all(MYSQLI_ASSOC);
        $stmt_students->close();
        
        $row['students'] = $students;
        $sections[] = $row;
    }
    $stmt->close();
    return $sections;
}

/**
 * Fetch all subjects
 */
function fetchSubjects($conn) {
    $subjects = [];
    $stmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    $stmt->close();
    return $subjects;
}

/**
 * Fetch all students
 */
function fetchStudents($conn) {
    $students = [];
    $stmt = $conn->prepare("SELECT student_id, first_name, last_name FROM students ORDER BY first_name ASC, last_name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
    return $students;
}

// Fetch data from database
$sections = fetchSections($conn);
$subjects = fetchSubjects($conn);
$students = fetchStudents($conn);

// Fetch CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="container mt-4">
    <h2>
        Grade Management 
        <i class="fas fa-info-circle" data-toggle="tooltip" title="Manage and input student grades across various assignments and assessments."></i>
    </h2>
    <p>Input and manage grades for students, handle bulk uploads via CSV, and ensure data integrity across all grading components.</p>

    <!-- Alert Placeholder -->
    <div id="alertPlaceholder"></div>

    <!-- Grade Input Form -->
    <form id="gradeForm" class="mb-4" action="/AcadMeter/server/controllers/teacher_dashboard_functions.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Section, Subject, and Student Selection -->
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="sectionSelect">
                    <i class="fas fa-chalkboard-teacher"></i> Select Section 
                    <i class="fas fa-info-circle" data-toggle="tooltip" title="Choose the section to manage grades."></i>
                </label>
                <select id="sectionSelect" name="section" class="form-control" required>
                    <option value="">-- Select Section --</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= $section['section_id'] ?>"><?= htmlspecialchars($section['section_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="subjectSelect">
                    <i class="fas fa-book"></i> Select Subject 
                    <i class="fas fa-info-circle" data-toggle="tooltip" title="Choose the subject for which to enter grades."></i>
                </label>
                <select id="subjectSelect" name="subject" class="form-control" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="studentSelect">
                    <i class="fas fa-user"></i> Select Student 
                    <i class="fas fa-info-circle" data-toggle="tooltip" title="Select the student for whom to enter grades."></i>
                </label>
                <input type="search" id="studentSearch" class="form-control" placeholder="Search student...">
                <select id="studentSelect" name="student" class="form-control mt-2" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['student_id'] ?>"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- CSV Upload Section -->
        <div class="form-group">
            <label for="csvFile">
                <i class="fas fa-file-upload"></i> Upload CSV for Grades 
                <i class="fas fa-info-circle" data-toggle="tooltip" title="Upload a CSV file with grades. Format: Student ID, Quiz Scores, Assignments, Extracurricular, Midterm, Final."></i>
            </label>
            <input type="file" name="csvFile" id="csvFile" class="form-control-file" accept=".csv">
            <button type="button" class="btn btn-secondary mt-2" id="uploadCSV">Upload & Process CSV</button>
        </div>

        <!-- Quarter Tabs -->
        <ul class="nav nav-tabs" id="quarterTabs" role="tablist">
            <?php for ($q = 1; $q <= 4; $q++): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $q === 1 ? 'active' : '' ?>" id="quarter<?= $q ?>-tab" data-toggle="tab" href="#quarter<?= $q ?>" role="tab">
                        Quarter <?= $q ?> <i class="fas fa-info-circle" data-toggle="tooltip" title="Input grades for Quarter <?= $q ?>."></i>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>

        <!-- Tab Content for Each Quarter -->
        <div class="tab-content" id="quarterTabContent">
            <?php for ($q = 1; $q <= 4; $q++): ?>
                <div class="tab-pane fade <?= $q === 1 ? 'show active' : '' ?>" id="quarter<?= $q ?>" role="tabpanel">
                    <div class="mt-4">
                        <h5>Quarter <?= $q ?> Grades <i class="fas fa-info-circle" data-toggle="tooltip" title="Manage quizzes, assignments, extracurricular activities, and exams for each quarter."></i></h5>

                        <!-- Quizzes Section -->
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-primary text-white">Quizzes 
                                <i class="fas fa-info-circle" data-toggle="tooltip" title="Add quizzes with scores, total items, and weights to calculate final grades."></i>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Quiz Name</th>
                                            <th>Score</th>
                                            <th>Total Items</th>
                                            <th>Weight (%)</th>
                                            <th>Weighted Grade</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="quarter<?= $q ?>QuizGrades">
                                        <tr>
                                            <td><input type="text" class="form-control" placeholder="Quiz 1" required></td>
                                            <td><input type="number" class="form-control score" placeholder="Score" min="0" required></td>
                                            <td><input type="number" class="form-control items" placeholder="Total Items" min="1" required></td>
                                            <td><input type="number" class="form-control weight" placeholder="Weight" min="0" max="100" required></td>
                                            <td><span class="weighted-grade">0%</span></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-primary btn-sm add-quiz-row" data-quarter="<?= $q ?>">Add Quiz</button>
                            </div>
                        </div>

                        <!-- Assignments Section -->
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-success text-white">Assignments 
                                <i class="fas fa-info-circle" data-toggle="tooltip" title="Add assignments with scores, total items, and weights to calculate final grades."></i>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Assignment Name</th>
                                            <th>Score</th>
                                            <th>Total Items</th>
                                            <th>Weight (%)</th>
                                            <th>Weighted Grade</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="quarter<?= $q ?>AssignmentGrades">
                                        <tr>
                                            <td><input type="text" class="form-control" placeholder="Assignment 1" required></td>
                                            <td><input type="number" class="form-control score" placeholder="Score" min="0" required></td>
                                            <td><input type="number" class="form-control items" placeholder="Total Items" min="1" required></td>
                                            <td><input type="number" class="form-control weight" placeholder="Weight" min="0" max="100" required></td>
                                            <td><span class="weighted-grade">0%</span></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-primary btn-sm add-assignment-row" data-quarter="<?= $q ?>">Add Assignment</button>
                            </div>
                        </div>

                        <!-- Extracurricular Activities Section -->
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-warning text-white">Extracurricular Activities 
                                <i class="fas fa-info-circle" data-toggle="tooltip" title="Add extracurricular activities with scores and total items."></i>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Activity Name</th>
                                            <th>Score</th>
                                            <th>Total Items</th>
                                            <th>Weighted Grade</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="quarter<?= $q ?>ExtracurricularGrades">
                                        <tr>
                                            <td><input type="text" class="form-control" placeholder="Activity 1" required></td>
                                            <td><input type="number" class="form-control score" placeholder="Score" min="0" required></td>
                                            <td><input type="number" class="form-control items" placeholder="Total Items" min="1" required></td>
                                            <td><span class="weighted-grade">0%</span></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-primary btn-sm add-extracurricular-row" data-quarter="<?= $q ?>">Add Activity</button>
                            </div>
                        </div>

                        <!-- Exams Section (Midterm and Finals) -->
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-danger text-white">Exams 
                                <i class="fas fa-info-circle" data-toggle="tooltip" title="Enter scores and total items for midterm and final exams."></i>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Exam Type</th>
                                            <th>Score</th>
                                            <th>Total Items</th>
                                            <th>Weight (%)</th>
                                            <th>Weighted Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Midterm</td>
                                            <td><input type="number" class="form-control score-midterm" placeholder="Score" min="0" required></td>
                                            <td><input type="number" class="form-control items-midterm" placeholder="Total Items" min="1" required></td>
                                            <td><input type="number" class="form-control weight-midterm" placeholder="Weight" min="0" max="100" value="30" required></td>
                                            <td><span class="weighted-grade-midterm">0%</span></td>
                                        </tr>
                                        <tr>
                                            <td>Finals</td>
                                            <td><input type="number" class="form-control score-final" placeholder="Score" min="0" required></td>
                                            <td><input type="number" class="form-control items-final" placeholder="Total Items" min="1" required></td>
                                            <td><input type="number" class="form-control weight-final" placeholder="Weight" min="0" max="100" value="40" required></td>
                                            <td><span class="weighted-grade-final">0%</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Compute Grade Button -->
                        <div class="text-right">
                            <button type="button" class="btn btn-success compute-grade" data-quarter="<?= $q ?>">Compute Quarter <?= $q ?> Grade</button>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Save All Grades Button -->
        <div class="text-right mt-4">
            <button type="submit" class="btn btn-primary">Save All Grades</button>
        </div>
    </form>

    <!-- Manage Subjects Section -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h4>Manage Subjects <i class="fas fa-info-circle" data-toggle="tooltip" title="Add, update, or delete subjects to ensure data integrity."></i></h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-hover" id="manageSubjectsTable">
                <thead class="thead-light">
                    <tr>
                        <th>Subject ID</th>
                        <th>Subject Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <tr id="subjectRow<?= htmlspecialchars($subject['subject_id']) ?>">
                            <td><?= htmlspecialchars($subject['subject_id']) ?></td>
                            <td id="subjectName<?= htmlspecialchars($subject['subject_id']) ?>"><?= htmlspecialchars($subject['subject_name']) ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-sm btn-info edit-subject" data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>" data-subject-name="<?= htmlspecialchars($subject['subject_name']) ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger delete-subject" data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Add Subject Button -->
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSubjectModal">
                <i class="fas fa-plus"></i> Add New Subject
            </button>
        </div>
    </div>

    <!-- Modals for Editing and Deleting Subjects -->

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="editSubjectForm">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Edit Subject</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" id="editSubjectId" name="subject_id">
                <div class="form-group">
                    <label for="editSubjectName">Subject Name</label>
                    <input type="text" id="editSubjectName" name="subject_name" class="form-control" required>
                </div>
            </div>
            
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Save Changes</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Subject Confirmation Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="deleteSubjectForm">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Delete Subject</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" id="deleteSubjectId" name="subject_id">
                <p>Are you sure you want to delete the subject "<strong id="deleteSubjectName"></strong>"? This action cannot be undone.</p>
            </div>
            
            <div class="modal-footer">
              <button type="submit" class="btn btn-danger">Yes, Delete</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Cancel</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="addSubjectForm">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Add New Subject</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-group">
                    <label for="newSubjectName">Subject Name</label>
                    <input type="text" id="newSubjectName" name="subject_name" class="form-control" placeholder="Enter subject name..." required>
                </div>
            </div>
            
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Add Subject</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
          </div>
        </form>
      </div>
    </div>
</div>

