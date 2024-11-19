<?php
// File: C:\xampp\htdocs\AcadMeter\public\views\class_management.php

// Check if the necessary variables are set
$sections = isset($sections) ? $sections : [];
$students = isset($students) ? $students : [];
$subjects = isset($subjects) ? $subjects : [];

// Fetch subjects if not already set
if (empty($subjects)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM subjects ORDER BY subject_name");
        $stmt->execute();
        $result = $stmt->get_result();
        $subjects = $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching subjects: " . $e->getMessage());
        $error_message = "An error occurred while fetching subjects. Please try again later.";
    }
}

// Fetch class roster data
function getClassRoster($conn) {
    $sql = "SELECT s.section_id, s.section_name, sub.subject_name, sub.subject_id, st.first_name, st.last_name, st.student_id
            FROM sections s
            LEFT JOIN section_subjects ss ON s.section_id = ss.section_id
            LEFT JOIN subjects sub ON ss.subject_id = sub.subject_id
            LEFT JOIN section_students sst ON s.section_id = sst.section_id
            LEFT JOIN students st ON sst.student_id = st.student_id
            WHERE s.instructor_id = ?
            ORDER BY s.section_name, sub.subject_name, st.last_name, st.first_name";
    
    $roster = [];

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sectionName = $row['section_name'];
                if (!isset($roster[$sectionName])) {
                    $roster[$sectionName] = ['section_id' => $row['section_id'], 'subjects' => [], 'students' => []];
                }
                if ($row['subject_name'] && !in_array(['name' => $row['subject_name'], 'id' => $row['subject_id']], $roster[$sectionName]['subjects'])) {
                    $roster[$sectionName]['subjects'][] = ['name' => $row['subject_name'], 'id' => $row['subject_id']];
                }
                if ($row['first_name'] && $row['last_name']) {
                    $studentName = $row['first_name'] . ' ' . $row['last_name'];
                    if (!in_array(['name' => $studentName, 'id' => $row['student_id']], $roster[$sectionName]['students'])) {
                        $roster[$sectionName]['students'][] = ['name' => $studentName, 'id' => $row['student_id']];
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error in getClassRoster: " . $e->getMessage());
        return false;
    }

    return $roster;
}

// Fetch class roster data with error handling
$classRoster = getClassRoster($conn);

if ($classRoster === false) {
    $error_message = "An error occurred while fetching the class roster. Please try again later.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/AcadMeter/public/assets/css/styles.css">
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .quarter-tabs {
            margin-bottom: 20px;
        }
        .tab-btn {
            margin-right: 10px;
        }
        .tab-btn.active {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h3>Class Management</h3>

        <!-- Alert Placeholder -->
        <div id="classManagementAlert"></div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="content-section">
            <div class="quarter-tabs">
                <button class="tab-btn active" data-tab="sections">Sections</button>
                <button class="tab-btn" data-tab="students">Students</button>
                <button class="tab-btn" data-tab="subjects">Subjects</button>
                <button class="tab-btn" data-tab="roster">Class Roster</button>
            </div>

            <!-- Sections Tab -->
            <div class="tab-content active" id="sections-tab">
                <h4 class="section-title">Add New Section</h4>
                <form id="addSectionForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label for="newSectionName">Section Name</label>
                        <input type="text" class="form-control" id="newSectionName" name="section_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Section</button>
                </form>
            </div>

            <!-- Students Tab -->
            <div class="tab-content" id="students-tab">
                <h4 class="section-title">Assign Student to Section</h4>
                <form id="assignStudentForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label for="studentSelect">Select Student</label>
                        <select class="form-control" id="studentSelect" name="student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= htmlspecialchars($student['student_id']) ?>">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sectionSelect">Select Section</label>
                        <select class="form-control" id="sectionSelect" name="section_id" required>
                            <option value="">-- Select Section --</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= htmlspecialchars($section['section_id']) ?>">
                                    <?= htmlspecialchars($section['section_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Assign Student</button>
                </form>
            </div>

            <!-- Subjects Tab -->
            <div class="tab-content" id="subjects-tab">
                <h4 class="section-title">Manage Subjects</h4>
                <form id="addSubjectForm" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label for="newSubjectName">Add New Subject</label>
                        <input type="text" class="form-control" id="newSubjectName" name="subject_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </form>

                <h5 class="mb-3">Assign Subject to Section</h5>
                <form id="assignSubjectForm" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label for="subjectSelect">Select Subject</label>
                        <select class="form-control" id="subjectSelect" name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject['subject_id']) ?>">
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sectionSubjectSelect">Select Section</label>
                        <select class="form-control" id="sectionSubjectSelect" name="section_id" required>
                            <option value="">-- Select Section --</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= htmlspecialchars($section['section_id']) ?>">
                                    <?= htmlspecialchars($section['section_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Assign Subject</button>
                </form>

                <h5 class="mb-3">Subject List</h5>
                <div class="table-responsive">
                    <table class="table" id="manageSubjectsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $subject): ?>
                                <tr id="subjectRow<?= htmlspecialchars($subject['subject_id']) ?>">
                                    <td><?= htmlspecialchars($subject['subject_id']) ?></td>
                                    <td id="subjectName<?= htmlspecialchars($subject['subject_id']) ?>"><?= htmlspecialchars($subject['subject_name']) ?></td>
                                    <td>
                                        <button class="btn btn-sm edit-subject" data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>" data-subject-name="<?= htmlspecialchars($subject['subject_name']) ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-subject" data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>" data-subject-name="<?= htmlspecialchars($subject['subject_name']) ?>">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Class Roster Tab -->
            <div class="tab-content" id="roster-tab">
                <h4 class="section-title">Class Roster</h4>
                <div id="classRosterContainer">
                    <?php if (isset($error_message)): ?>
                        <p class="text-danger"><?= htmlspecialchars($error_message) ?></p>
                    <?php elseif (empty($classRoster)): ?>
                        <p class="text-muted">No class roster data available.</p>
                    <?php else: ?>
                        <?php foreach ($classRoster as $sectionName => $data): ?>
                            <div class="section-roster mb-4">
                                <h4>
                                    <?= htmlspecialchars($sectionName) ?>
                                    <button class="btn btn-sm btn-outline-danger float-right delete-section" data-section-id="<?= htmlspecialchars($data['section_id']) ?>" data-section-name="<?= htmlspecialchars($sectionName) ?>">
                                        <i class="fas fa-trash-alt"></i> Delete Section
                                    </button>
                                </h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Subjects</h5>
                                        <ul class="list-group">
                                            <?php if (empty($data['subjects'])): ?>
                                                <li class="list-group-item">No subjects assigned</li>
                                            <?php else: ?>
                                                <?php foreach ($data['subjects'] as $subject): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <?= htmlspecialchars($subject['name']) ?>
                                                        <button class="btn btn-sm btn-outline-danger remove-subject" data-section="<?= htmlspecialchars($sectionName) ?>" data-subject="<?= htmlspecialchars($subject['name']) ?>" data-subject-id="<?= htmlspecialchars($subject['id']) ?>" data-section-id="<?= htmlspecialchars($data['section_id']) ?>">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Students</h5>
                                        <ul class="list-group">
                                            <?php if (empty($data['students'])): ?>
                                                <li class="list-group-item">No students assigned</li>
                                            <?php else: ?>
                                                <?php foreach ($data['students'] as $student): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <?= htmlspecialchars($student['name']) ?>
                                                        <button class="btn btn-sm btn-outline-danger remove-student" data-section="<?= htmlspecialchars($sectionName) ?>" data-student="<?= htmlspecialchars($student['name']) ?>" data-student-id="<?= htmlspecialchars($student['id']) ?>" data-section-id="<?= htmlspecialchars($data['section_id']) ?>">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" role="dialog" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editSubjectFormModal">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="editSubjectId" name="subject_id">
                        <div class="form-group">
                            <label for="editSubjectName">Subject Name</label>
                            <input type="text" class="form-control" id="editSubjectName" name="subject_name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Subject Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1" role="dialog"
         aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSubjectModalLabel">Delete Subject</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="deleteSubjectFormModal">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="deleteSubjectId" name="subject_id">
                        <p>Are you sure you want to delete the subject "<strong id="deleteSubjectName"></strong>"?</p>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Section Modal -->
    <div class="modal fade" id="deleteSectionModal" tabindex="-1" role="dialog"
         aria-labelledby="deleteSectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSectionModalLabel">Delete Section</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="deleteSectionFormModal">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" id="deleteSectionId" name="section_id">
                        <p>Are you sure you want to delete the section "<strong id="deleteSectionName"></strong>"? This action cannot be undone and will remove all associated students and subjects.</p>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </form>
                    <div id="deleteSectionAlert" class="alert alert-danger mt-3 d-none"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="/AcadMeter/public/assets/js/class_management.js"></script>
</body>
</html>