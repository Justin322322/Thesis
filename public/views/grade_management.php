<?php
// File: C:\xampp\htdocs\AcadMeter\public\views\grade_management.php

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

// Fetch subjects for the instructor (assuming each section is tied to a subject)
$stmt = $conn->prepare("SELECT DISTINCT su.subject_id, su.subject_name 
                        FROM sections s 
                        JOIN subjects su ON s.subject_id = su.subject_id 
                        WHERE s.instructor_id = ?");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$subjects_result = $stmt->get_result();
$subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch sections for the instructor
$stmt = $conn->prepare("SELECT s.section_id, s.section_name, su.subject_name, su.subject_id
                        FROM sections s 
                        JOIN subjects su ON s.subject_id = su.subject_id 
                        WHERE s.instructor_id = ?");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$sections_result = $stmt->get_result();
$sections = $sections_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$quarters = ['1st', '2nd', '3rd', '4th'];

$components = [
    'written_works' => ['name' => 'Written Works', 'weight' => 30, 'initial_items' => 2],
    'performance_tasks' => ['name' => 'Performance Tasks', 'weight' => 50, 'initial_items' => 2],
    'quarterly_assessment' => ['name' => 'Quarterly Assessment', 'weight' => 20, 'items' => 1],
];

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Management</title>
    <!-- Include necessary CSS and JS here -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Font Awesome for icons if not already included -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <!-- Link to your external JavaScript file -->
    <script src="/AcadMeter/public/assets/js/grade_management.js" defer></script>
    <!-- Include your CSS files here -->
    <link rel="stylesheet" href="/AcadMeter/public/assets/css/styles.css">
    <!-- Include Bootstrap CSS if needed for modal (optional, as user requested no Bootstrap additions) -->
    <!-- If you choose not to use Bootstrap for modals, you can create a custom modal using CSS and JS -->
    <style>
        /* Simple modal styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 50%; 
            border-radius: 5px;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div id="grade-management" class="content-section">
        <h2 class="mb-4">Grade Management <i class="fas fa-info-circle text-primary" data-toggle="tooltip" title="Manage and record student grades for different components."></i></h2>

        <div class="card shadow-sm">
            <div class="card-body">
                <form id="gradeForm" method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="section">Select Section:</label>
                            <select id="section" name="section" class="form-control" required>
                                <option value="">-- Select Section --</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo htmlspecialchars($section['section_id']); ?>" data-subject="<?php echo htmlspecialchars($section['subject_name']); ?>">
                                        <?php echo htmlspecialchars($section['section_name']); ?> - <?php echo htmlspecialchars($section['subject_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="subject">Subject:</label>
                            <input type="text" id="subject" name="subject" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" id="openCreateSectionModal" class="btn btn-success">
                            <i class="fas fa-plus"></i> Create New Section
                        </button>
                    </div>

                    <div class="quarter-tabs mb-3">
                        <?php foreach ($quarters as $index => $quarter): ?>
                            <button type="button" class="btn btn-outline-primary tab-btn<?php echo $index === 0 ? ' active' : ''; ?>" data-quarter="<?php echo $quarter; ?>"><?php echo $quarter; ?> Quarter</button>
                        <?php endforeach; ?>
                    </div>

                    <div class="table-responsive">
                        <table id="gradeTable" class="table table-striped table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Student Name</th>
                                    <?php foreach ($components as $key => $component): ?>
                                        <th>
                                            <?php echo $component['name']; ?> (<?php echo $component['weight']; ?>%)
                                        </th>
                                    <?php endforeach; ?>
                                    <th>Initial Grade</th>
                                    <th>Quarterly Grade</th>
                                </tr>
                            </thead>
                            <tbody id="gradeTableBody">
                                <!-- Student rows will be dynamically added here via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-primary save-grades mt-3">
                        <i class="fas fa-save"></i> Save Grades
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Creating New Sections -->
    <div id="createSectionModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Create New Section</h3>
            <form id="createSectionForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="new_section_subject">Select Subject:</label>
                    <select id="new_section_subject" name="subject_id" class="form-control" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_section_number">Section Number:</label>
                    <select id="new_section_number" name="section_number" class="form-control" required>
                        <option value="">-- Select Section Number --</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>">Section <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_section_name">Section Name:</label>
                    <input type="text" id="new_section_name" name="section_name" class="form-control" readonly>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Section
                </button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Existing JavaScript code for grade management...

        // Modal functionality
        const modal = document.getElementById('createSectionModal');
        const openModalBtn = document.getElementById('openCreateSectionModal');
        const closeModalSpan = document.querySelector('.close-modal');
        const newSectionNumberSelect = document.getElementById('new_section_number');
        const newSectionNameInput = document.getElementById('new_section_name');

        // Open modal
        openModalBtn.addEventListener('click', () => {
            modal.style.display = 'block';
        });

        // Close modal
        closeModalSpan.addEventListener('click', () => {
            modal.style.display = 'none';
            resetCreateSectionForm();
        });

        // Close modal when clicking outside the modal content
        window.addEventListener('click', (event) => {
            if (event.target == modal) {
                modal.style.display = 'none';
                resetCreateSectionForm();
            }
        });

        // Update section name based on selected number
        newSectionNumberSelect.addEventListener('change', function() {
            const number = this.value;
            if (number) {
                newSectionNameInput.value = 'Section ' + number;
            } else {
                newSectionNameInput.value = '';
            }
        });

        // Handle Create Section Form Submission
        const createSectionForm = document.getElementById('createSectionForm');
        createSectionForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'create_section');

            fetch('/AcadMeter/server/controllers/grade_management_controller.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Section created successfully!');
                        modal.style.display = 'none';
                        resetCreateSectionForm();
                        // Refresh sections dropdown
                        refreshSectionsDropdown();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error creating section:', error));
        });

        // Function to reset the create section form
        function resetCreateSectionForm() {
            createSectionForm.reset();
            newSectionNameInput.value = '';
        }

        // Function to refresh the sections dropdown after creating a new section
        function refreshSectionsDropdown() {
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;

            fetch('/AcadMeter/server/controllers/grade_management_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'fetch_sections', csrf_token: csrfToken, instructor_id: <?php echo json_encode($instructor_id); ?> })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const sectionSelect = document.getElementById('section');
                        sectionSelect.innerHTML = '<option value="">-- Select Section --</option>';
                        data.sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.section_id;
                            option.textContent = `${section.section_name} - ${section.subject_name}`;
                            option.setAttribute('data-subject', section.subject_name);
                            sectionSelect.appendChild(option);
                        });
                    } else {
                        alert('Failed to refresh sections: ' + data.message);
                    }
                })
                .catch(error => console.error('Error fetching sections:', error));
        }

    });
    </script>
</body>
</html>
