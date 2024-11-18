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
    $sql = "SELECT s.section_name, sub.subject_name, st.first_name, st.last_name 
            FROM sections s
            LEFT JOIN section_subjects ss ON s.section_id = ss.section_id
            LEFT JOIN subjects sub ON ss.subject_id = sub.subject_id
            LEFT JOIN section_students sst ON s.section_id = sst.section_id
            LEFT JOIN students st ON sst.student_id = st.student_id
            ORDER BY s.section_name, sub.subject_name, st.last_name, st.first_name";
    
    $roster = [];

    try {
        $result = $conn->query($sql);

        if ($result === false) {
            throw new Exception("Query failed: " . $conn->error);
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sectionName = $row['section_name'];
                if (!isset($roster[$sectionName])) {
                    $roster[$sectionName] = ['subjects' => [], 'students' => []];
                }
                if ($row['subject_name'] && !in_array($row['subject_name'], $roster[$sectionName]['subjects'])) {
                    $roster[$sectionName]['subjects'][] = $row['subject_name'];
                }
                if ($row['first_name'] && $row['last_name']) {
                    $studentName = $row['first_name'] . ' ' . $row['last_name'];
                    if (!in_array($studentName, $roster[$sectionName]['students'])) {
                        $roster[$sectionName]['students'][] = $studentName;
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
<div class="container-fluid">
    <h3>Class Management</h3>

    <!-- Alert Placeholder -->
    <div id="classManagementAlert"></div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Assign Student to Section -->
    <div class="card mb-4">
        <div class="card-header">
            Assign Student to Section
        </div>
        <div class="card-body">
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
    </div>

    <!-- Assign Subject to Section -->
    <div class="card mb-4">
        <div class="card-header">
            Assign Subject to Section
        </div>
        <div class="card-body">
            <form id="assignSubjectForm">
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
        </div>
    </div>

    <!-- Add New Subject -->
    <div class="card mb-4">
        <div class="card-header">
            Add New Subject
        </div>
        <div class="card-body">
            <form id="addSubjectForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-group">
                    <label for="newSubjectName">Subject Name</label>
                    <input type="text" class="form-control" id="newSubjectName" name="subject_name" required>
                </div>
                <button type="submit" class="btn btn-success">Add Subject</button>
            </form>
        </div>
    </div>

    <!-- Manage Subjects Table -->
    <div class="card mb-4">
        <div class="card-header">
            Manage Subjects
        </div>
        <div class="card-body">
            <?php if (empty($subjects)): ?>
                <p class="text-muted">No subjects available.</p>
            <?php else: ?>
                <table class="table table-bordered" id="manageSubjectsTable">
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
                                    <button class="btn btn-info btn-sm edit-subject" data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>" data-subject-name="<?= htmlspecialchars($subject['subject_name']) ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm delete-subject" data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>" data-subject-name="<?= htmlspecialchars($subject['subject_name']) ?>">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Class Roster -->
    <div class="card mb-4">
        <div class="card-header">
            Class Roster
        </div>
        <div class="card-body">
            <div id="classRosterContainer">
                <?php if (isset($error_message)): ?>
                    <p class="text-danger"><?= htmlspecialchars($error_message) ?></p>
                <?php elseif (empty($classRoster)): ?>
                    <p class="text-muted">No class roster data available.</p>
                <?php else: ?>
                    <?php foreach ($classRoster as $sectionName => $data): ?>
                        <div class="section-roster mb-4">
                            <h4><?= htmlspecialchars($sectionName) ?></h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Subjects</h5>
                                    <ul class="list-group">
                                        <?php if (empty($data['subjects'])): ?>
                                            <li class="list-group-item">No subjects assigned</li>
                                        <?php else: ?>
                                            <?php foreach ($data['subjects'] as $subject): ?>
                                                <li class="list-group-item"><?= htmlspecialchars($subject) ?></li>
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
                                                <li class="list-group-item"><?= htmlspecialchars($student) ?></li>
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

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" role="dialog" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
            <div id="editSubjectAlert" class="alert alert-danger mt-3 d-none"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Subject Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1" role="dialog" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteSubjectModalLabel">Delete Subject</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="deleteSubjectFormModal">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" id="deleteSubjectId" name="subject_id">
                <p>Are you sure you want to delete the subject "<strong id="deleteSubjectName"></strong>"? This action cannot be undone.</p>
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </form>
            <div id="deleteSubjectAlert" class="alert alert-danger mt-3 d-none"></div>
          </div>
        </div>
      </div>
    </div>
</div>