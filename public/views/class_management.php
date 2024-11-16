<?php
// File: C:\xampp\htdocs\AcadMeter\public\views\class_management.php

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is authenticated and has the Instructor role
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.php');
    exit;
}

require_once __DIR__ . '/../../config/db_connection.php';

// Fetch instructor_id for the current user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$instructorData = $result->fetch_assoc();
$instructor_id = isset($instructorData['instructor_id']) ? $instructorData['instructor_id'] : 0;
$stmt->close();

// Function to fetch sections
function fetchSections($conn, $instructor_id) {
    $sections = [];
    $stmt = $conn->prepare("
        SELECT s.section_id, s.section_name, sub.subject_name, sub.subject_id
        FROM sections s
        LEFT JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.instructor_id = ?
        ORDER BY s.section_name ASC
    ");
    $stmt->bind_param("i", $instructor_id);
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

// Function to fetch subjects
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

// Function to fetch students
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
$sections = fetchSections($conn, $instructor_id);
$subjects = fetchSubjects($conn);
$students = fetchStudents($conn);

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="content-section">
    <h2 class="mb-4">
        Class Management
        <i class="fas fa-info-circle text-primary" data-toggle="tooltip" title="Manage class sections, assign subjects, and handle student assignments."></i>
    </h2>

    <!-- Alert Placeholder -->
    <div id="alertPlaceholder"></div>

    <!-- Assign Students and Assign Subject Cards -->
    <div class="row">
        <!-- Assign Students to Section -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Assign Students to Section</h5>
                </div>
                <div class="card-body">
                    <form id="assignStudentsForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="form-group">
                            <label for="assignSectionSelect">Select Section</label>
                            <select id="assignSectionSelect" name="section_id" class="form-control" required>
                                <option value="">-- Select Section --</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= htmlspecialchars($section['section_id']) ?>">
                                        <?= htmlspecialchars($section['section_name']) ?><?= !empty($section['subject_name']) ? ' (Subject: ' . htmlspecialchars($section['subject_name']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="studentSearchAssign">Search Students</label>
                            <input type="search" id="studentSearchAssign" class="form-control mb-2" placeholder="Search student..." onkeyup="filterOptions('assignStudentSelect', 'studentSearchAssign')">
                            <select id="assignStudentSelect" name="students[]" class="form-control select-multiple" multiple required>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= htmlspecialchars($student['student_id']) ?>">
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Hold Ctrl (Windows) / Command (Mac) to select multiple students.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Assign Students
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assign Subject to Section -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-book-reader"></i> Assign Subject to Section</h5>
                </div>
                <div class="card-body">
                    <form id="assignSubjectForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="form-group">
                            <label for="assignSubjectSectionSelect">Select Section</label>
                            <select id="assignSubjectSectionSelect" name="section_id" class="form-control" required>
                                <option value="">-- Select Section --</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= htmlspecialchars($section['section_id']) ?>">
                                        <?= htmlspecialchars($section['section_name']) ?><?= !empty($section['subject_name']) ? ' (Current Subject: ' . htmlspecialchars($section['subject_name']) . ')' : ' (No Subject Assigned)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="assignSubjectSelect">Select Subject</label>
                            <select id="assignSubjectSelect" name="subject_id" class="form-control" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= htmlspecialchars($subject['subject_id']) ?>">
                                        <?= htmlspecialchars($subject['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-book-reader"></i> Assign Subject
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Subject and Manage Subjects -->
    <div class="row">
        <!-- Add New Subject -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Add New Subject</h5>
                </div>
                <div class="card-body">
                    <form id="addSubjectForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="form-group">
                            <label for="newSubject">Subject Name</label>
                            <input type="text" id="newSubject" name="subject_name" class="form-control" placeholder="Enter subject name..." required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Subject
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Manage Subjects -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Manage Subjects</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="manageSubjectsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject ID</th>
                                    <th>Subject Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($subjects)): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr id="subjectRow<?= htmlspecialchars($subject['subject_id']) ?>">
                                            <td><?= htmlspecialchars($subject['subject_id']) ?></td>
                                            <td id="subjectName<?= htmlspecialchars($subject['subject_id']) ?>"><?= htmlspecialchars($subject['subject_name']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info edit-subject" data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>" data-subject-name="<?= htmlspecialchars($subject['subject_name']) ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-subject" data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No subjects found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Class Roster -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                Class Roster
                <i class="fas fa-info-circle text-light" data-toggle="tooltip" title="View the roster of students and assigned subjects in each section."></i>
            </h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="sectionRosterAccordion">
                <?php if (!empty($sections)): ?>
                    <?php foreach ($sections as $index => $section): ?>
                        <div class="card">
                            <div class="card-header" id="section<?= htmlspecialchars($section['section_id']) ?>Roster">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-toggle="collapse" data-target="#collapse<?= htmlspecialchars($section['section_id']) ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= htmlspecialchars($section['section_id']) ?>">
                                        <i class="fas fa-angle-down"></i>
                                        <?= htmlspecialchars($section['section_name']) ?>
                                        <?= !empty($section['subject_name']) ? ' - Subject: ' . htmlspecialchars($section['subject_name']) : ' - No Subject Assigned' ?>
                                        - <?= count($section['students']) ?> Students
                                    </button>
                                </h2>
                            </div>

                            <div id="collapse<?= htmlspecialchars($section['section_id']) ?>" class="collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="section<?= htmlspecialchars($section['section_id']) ?>Roster" data-parent="#sectionRosterAccordion">
                                <div class="card-body">
                                    <h6><i class="fas fa-book"></i> Assigned Subject:</h6>
                                    <ul class="list-group mb-3">
                                        <?php if (!empty($section['subject_name'])): ?>
                                            <li class="list-group-item"><?= htmlspecialchars($section['subject_name']) ?></li>
                                        <?php else: ?>
                                            <li class="list-group-item text-muted">No subject assigned to this section yet.</li>
                                        <?php endif; ?>
                                    </ul>

                                    <h6><i class="fas fa-users"></i> Students:</h6>
                                    <ul class="list-group">
                                        <?php if (!empty($section['students'])): ?>
                                            <?php foreach ($section['students'] as $student): ?>
                                                <li class="list-group-item">
                                                    <i class="fas fa-user"></i> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="list-group-item text-muted">No students assigned to this section yet.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <p class="text-center text-muted">No sections found.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Subject Modal -->
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" id="messageModalHeader">
        <h5 class="modal-title" id="messageModalLabel">Modal Title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="messageModalBody">
        Modal body content goes here.
      </div>
      <div class="modal-footer" id="messageModalFooter">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <!-- Optional additional buttons can be added here -->
      </div>
    </div>
  </div>
</div>

<!-- Include Custom JS -->
<script src="/AcadMeter/public/assets/js/class_management.js"></script>

<!-- Initialize tooltips and other JavaScript functionalities -->
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // Function to filter options in a select element
    function filterOptions(selectId, searchInputId) {
        const searchValue = document.getElementById(searchInputId).value.toLowerCase();
        const select = document.getElementById(selectId);
        for (let i = 0; i < select.options.length; i++) {
            const optionText = select.options[i].text.toLowerCase();
            select.options[i].style.display = optionText.includes(searchValue) ? 'block' : 'none';
        }
    }
</script>
