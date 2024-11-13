<!-- C:\xampp\htdocs\AcadMeter\public\views\class_management.php -->
<?php
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Management</title>
    <!-- Include Bootstrap CSS and Font Awesome for styling and icons -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/AcadMeter/public/assets/css/class_management.css">
    <style>
        /* Custom styles for better UX */
        .card-header h4, .card-header h5 {
            margin: 0;
        }
        .search-input {
            margin-bottom: 10px;
        }
        /* Additional custom styles */
        .tooltip-inner {
            max-width: 200px;
            /* Customize tooltip width */
        }
        .select-multiple {
            height: 200px;
            overflow-y: scroll;
        }
        /* Style for accordion buttons */
        .accordion .card-header button {
            text-align: left;
            width: 100%;
            color: #333;
            text-decoration: none;
            background: none;
            border: none;
            padding: 0;
            font-size: 1.1rem;
        }

        .accordion .card-header button:focus {
            outline: none;
        }

        .accordion .card-header button .fas {
            margin-right: 10px;
            transition: transform 0.2s;
        }

        .accordion .card-header button.collapsed .fas {
            transform: rotate(-90deg);
        }

        .accordion .card-header button:not(.collapsed) .fas {
            transform: rotate(0deg);
        }

        /* Additional styles for Delete and Update buttons */
        .action-buttons {
            float: right;
        }
        .action-buttons button {
            margin-left: 5px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2>
        Class Management 
        <i class="fas fa-info-circle" data-toggle="tooltip" title="Manage sections, assign students, and set subjects for specific sections."></i>
    </h2>
    <p>Assign students and subjects to sections, manage class rosters, and view current student assignments.</p>

    <!-- Alert Placeholder -->
    <div id="alertPlaceholder"></div>

    <!-- Assign Students to Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4>Assign Students to Section</h4>
        </div>
        <div class="card-body">
            <form id="assignStudentsForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-row">
                    <!-- Select Section -->
                    <div class="form-group col-md-6">
                        <label for="assignSectionSelect">
                            <i class="fas fa-chalkboard-teacher"></i> Select Section 
                            <i class="fas fa-info-circle" data-toggle="tooltip" title="Choose a section to assign students."></i>
                        </label>
                        <select id="assignSectionSelect" name="section_id" class="form-control" required>
                            <option value="">-- Select Section --</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= htmlspecialchars($section['section_id']) ?>">
                                    <?= htmlspecialchars($section['section_name']) ?><?= !empty($section['subject_name']) ? ' (Subject: ' . htmlspecialchars($section['subject_name']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Search Students -->
                    <div class="form-group col-md-6">
                        <label for="studentSearchAssign">
                            <i class="fas fa-search"></i> Search Students
                        </label>
                        <input type="search" id="studentSearchAssign" class="form-control search-input" placeholder="Search student..." onkeyup="filterOptions('assignStudentSelect', 'studentSearchAssign')">
                        <select id="assignStudentSelect" name="students[]" class="form-control select-multiple" multiple required>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= htmlspecialchars($student['student_id']) ?>">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Hold down the Ctrl (Windows) / Command (Mac) button to select multiple options.</small>
                    </div>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Assign Students to Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Subject to Section -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h4>Assign Subject to Section</h4>
        </div>
        <div class="card-body">
            <form id="assignSubjectForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-row">
                    <!-- Select Section -->
                    <div class="form-group col-md-6">
                        <label for="assignSubjectSectionSelect">
                            <i class="fas fa-chalkboard-teacher"></i> Select Section 
                            <i class="fas fa-info-circle" data-toggle="tooltip" title="Choose a section to assign a subject."></i>
                        </label>
                        <select id="assignSubjectSectionSelect" name="section_id" class="form-control" required>
                            <option value="">-- Select Section --</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= htmlspecialchars($section['section_id']) ?>">
                                    <?= htmlspecialchars($section['section_name']) ?><?= !empty($section['subject_name']) ? ' (Current Subject: ' . htmlspecialchars($section['subject_name']) . ')' : ' (No Subject Assigned)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Select Subject -->
                    <div class="form-group col-md-6">
                        <label for="assignSubjectSelect">
                            <i class="fas fa-book"></i> Select Subject
                            <i class="fas fa-info-circle" data-toggle="tooltip" title="Choose a subject to assign to the selected section."></i>
                        </label>
                        <select id="assignSubjectSelect" name="subject_id" class="form-control" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject['subject_id']) ?>">
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-book-reader"></i> Assign Subject to Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add New Subject -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h4>Add New Subject</h4>
        </div>
        <div class="card-body">
            <form id="addSubjectForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-row">
                    <!-- Subject Name -->
                    <div class="form-group col-md-8">
                        <label for="newSubject">
                            <i class="fas fa-plus-circle"></i> Subject Name 
                            <i class="fas fa-info-circle" data-toggle="tooltip" title="Enter the name of the new subject to add."></i>
                        </label>
                        <input type="text" id="newSubject" name="subject_name" class="form-control" placeholder="Enter subject name..." required>
                    </div>
                    <!-- Add Subject Button -->
                    <div class="form-group col-md-4 align-self-end">
                        <button type="button" id="addSubjectButton" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add Subject
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Subjects (New Section) -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-white">
            <h4>Manage Subjects</h4>
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
        </div>
    </div>

    <!-- Class Roster Display -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h4>
                Class Roster 
                <i class="fas fa-info-circle" data-toggle="tooltip" title="View the roster of students and assigned subjects in each section."></i>
            </h4>
        </div>
        <div class="card-body">
            <div class="accordion" id="sectionRosterAccordion">
                <?php foreach ($sections as $section): ?>
                    <div class="card">
                        <div class="card-header" id="section<?= htmlspecialchars($section['section_id']) ?>Roster">
                            <h5 class="mb-0">
                                <button class="btn btn-link <?= $section === reset($sections) ? '' : 'collapsed' ?>" type="button" data-toggle="collapse" data-target="#collapse<?= htmlspecialchars($section['section_id']) ?>" aria-expanded="<?= $section === reset($sections) ? 'true' : 'false' ?>" aria-controls="collapse<?= htmlspecialchars($section['section_id']) ?>">
                                    <i class="fas fa-angle-down"></i> 
                                    <?= htmlspecialchars($section['section_name']) ?> 
                                    <?= !empty($section['subject_name']) ? ' - Subject: ' . htmlspecialchars($section['subject_name']) : ' - No Subject Assigned' ?> 
                                    - <?= count($section['students']) ?> Students
                                </button>
                            </h5>
                        </div>

                        <div id="collapse<?= htmlspecialchars($section['section_id']) ?>" class="collapse <?= $section === reset($sections) ? 'show' : '' ?>" aria-labelledby="section<?= htmlspecialchars($section['section_id']) ?>Roster" data-parent="#sectionRosterAccordion">
                            <div class="card-body">
                                <!-- Assigned Subject -->
                                <h6><i class="fas fa-book"></i> Assigned Subject:</h6>
                                <ul class="list-group mb-3">
                                    <?php if (!empty($section['subject_name'])): ?>
                                        <li class="list-group-item"><?= htmlspecialchars($section['subject_name']) ?></li>
                                    <?php else: ?>
                                        <li class="list-group-item">No subject assigned to this section yet.</li>
                                    <?php endif; ?>
                                </ul>

                                <!-- Assigned Students -->
                                <h6><i class="fas fa-users"></i> Students:</h6>
                                <ul class="list-group">
                                    <?php if (!empty($section['students'])): ?>
                                        <?php foreach ($section['students'] as $student): ?>
                                            <li class="list-group-item">
                                                <i class="fas fa-user"></i> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="list-group-item">No students assigned to this section yet.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>

<!-- Include External JavaScript File -->
<script src="/AcadMeter/public/assets/js/class_management.js"></script>

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

</body>
</html>
