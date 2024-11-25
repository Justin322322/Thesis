<?php
// File: grade_management.php

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is authenticated and has the Instructor role
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Instructor') {
    header('Location: /AcadMeter/public/login.html');
    exit;
}

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../server/controllers/grade_management_controller.php';

$gradeManagementController = new GradeManagementController($conn);

// Use the user_id directly from the session
$instructor_id = $_SESSION['user_id'];

// Define quarters
$quarters = [1, 2, 3, 4];

// Fetch grade components from the database
$components = [];
$gradeComponentsQuery = "SELECT component_id, component_name, weight FROM grade_components";
$result = $conn->query($gradeComponentsQuery);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $components[] = [
            'component_id' => (int)$row['component_id'],
            'name' => $row['component_name'],
            'weight' => (float)$row['weight']
        ];
    }
} else {
    // Handle query failure
    die("Error fetching grade components: " . $conn->error);
}

// Define subcategories mapped by component_id
$subcategories = [
    1 => [ // Assuming component_id 1 is Written Works
        ['name' => 'Quizzes', 'description' => 'Short tests to assess understanding of topics.'],
        ['name' => 'Essays', 'description' => 'Longer written tasks evaluating depth of knowledge and argumentation.'],
        ['name' => 'Homework', 'description' => 'Tasks completed outside class to reinforce learning.'],
    ],
    2 => [ // Assuming component_id 2 is Performance Tasks
        ['name' => 'Projects', 'description' => 'Group or individual projects involving creativity and research.'],
        ['name' => 'Presentations', 'description' => 'Oral presentations demonstrating understanding.'],
        ['name' => 'Lab Work', 'description' => 'Practical experiments and reports.'],
    ],
    3 => [ // Assuming component_id 3 is Quarterly Assessment
        ['name' => 'Quarterly Exam', 'description' => 'Comprehensive test summarizing student performance.'],
    ]
];

// Function to get ordinal suffix for quarters
function getOrdinalSuffix($number) {
    if (!in_array(($number % 100), [11, 12, 13])) {
        switch ($number % 10) {
            case 1:  return $number . 'st';
            case 2:  return $number . 'nd';
            case 3:  return $number . 'rd';
        }
    }
    return $number . 'th';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="/AcadMeter/public/assets/css/styles.css">
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .subcategory-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .subcategory-row input {
            margin-right: 5px;
        }
        .grade-input, .subcategory-score {
            width: 60px !important;
            text-align: right;
            padding: 4px 8px !important;
            height: 30px !important;
        }
        .subcategory-name {
            flex-grow: 1;
            margin-right: 8px;
            font-size: 14px;
            cursor: help;
        }
        .component-total {
            font-weight: bold;
            color: #495057;
            margin-bottom: 8px;
            padding: 4px 8px;
            background: #e9ecef;
            border-radius: 4px;
            text-align: center;
        }
        .subcategories {
            margin-top: 8px;
        }
        .initial-grade, .quarterly-grade, .remarks {
            font-weight: bold;
        }
        .component-header {
            font-weight: bold;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .grade-cell {
            min-width: 200px;
            padding: 8px !important;
        }
        .student-name {
            font-weight: 500;
            color: #495057;
        }
        .failed-grade {
            color: #dc3545;
        }
        .passed-grade {
            color: #28a745;
        }
        .add-subcategory-btn, .remove-subcategory-btn {
            padding: 0;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            font-size: 16px;
            border-radius: 50%;
        }
        .remove-subcategory-btn {
            margin-left: 5px;
        }
        .component-description {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }
        .grade-input::-webkit-inner-spin-button, 
        .grade-input::-webkit-outer-spin-button,
        .subcategory-score::-webkit-inner-spin-button,
        .subcategory-score::-webkit-outer-spin-button { 
            -webkit-appearance: none;
            margin: 0;
        }
        .grade-input[type=number],
        .subcategory-score[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body>
    <div id="grade-management" class="content-section">
        <h2 class="mb-4"><i class="fas fa-info-circle text-primary" data-toggle="tooltip" title="Manage and record student grades for different components."></i></h2>

        <div class="card shadow-sm">
            <div class="card-body">
                <form id="gradeForm" method="post" action="">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="section">Select Section:</label>
                            <select id="section" name="section" class="form-control" required>
                                <option value="">-- Select Section --</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="subject">Select Subject:</label>
                            <select id="subject" name="subject" class="form-control" required disabled>
                                <option value="">-- Select Subject --</option>
                            </select>
                        </div>
                    </div>

                    <div class="quarter-tabs mb-3">
                        <?php foreach ($quarters as $quarter): ?>
                            <button type="button" class="btn btn-outline-primary tab-btn<?php echo $quarter === 1 ? ' active' : ''; ?>" data-quarter="<?php echo $quarter; ?>">
                                <?php echo getOrdinalSuffix($quarter) . ' Quarter'; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="grade-tabs mb-3">
                        <button type="button" class="btn btn-outline-secondary grade-tab active" data-tab="summary">Summary</button>
                        <button type="button" class="btn btn-outline-secondary grade-tab" data-tab="detailed">Detailed Scoring</button>
                    </div>

                    <div id="summary-tab" class="tab-content active">
                        <div class="table-responsive">
                            <table id="gradeTable" class="table table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Student Name</th>
                                        <?php foreach ($components as $component): ?>
                                            <th>
                                                <div class="component-header">
                                                    <?php echo htmlspecialchars($component['name']); ?> (<?php echo htmlspecialchars($component['weight']); ?>%)
                                                </div>
                                                <div class="component-description">
                                                    Total score for all <?php echo strtolower(htmlspecialchars($component['name'])); ?>
                                                </div>
                                            </th>
                                        <?php endforeach; ?>
                                        <th>Initial Grade</th>
                                        <th>Quarterly Grade</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="gradeTableBody">
                                    <!-- Student rows will be dynamically added here via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="detailed-tab" class="tab-content">
                        <div class="table-responsive">
                            <table id="detailedGradeTable" class="table table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Student Name</th>
                                        <?php foreach ($components as $component): ?>
                                            <th class="grade-cell">
                                                <div class="component-header">
                                                    <?php echo htmlspecialchars($component['name']); ?> (<?php echo htmlspecialchars($component['weight']); ?>%)
                                                    <button type="button" class="btn btn-sm btn-outline-primary add-subcategory-btn" data-component-id="<?php echo htmlspecialchars($component['component_id']); ?>">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <div class="component-description">
                                                    Average of all <?php echo strtolower(htmlspecialchars($component['name'])); ?> activities
                                                </div>
                                            </th>
                                        <?php endforeach; ?>
                                        <th>Initial Grade</th>
                                        <th>Quarterly Grade</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="detailedGradeTableBody">
                                    <!-- Student rows with detailed scoring will be dynamically added here via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary save-grades mt-3">
                        <i class="fas fa-save"></i> Save Grades
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Subcategory Modal -->
    <div class="modal fade" id="subcategoryModal" tabindex="-1" role="dialog" aria-labelledby="subcategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Subcategory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalComponentId" value="">
                    <div class="form-group">
                        <label for="subcategorySelect">Subcategory</label>
                        <select id="subcategorySelect" class="form-control">
                            <option value="">-- Select Subcategory --</option>
                            <!-- Options will be dynamically populated based on component_id -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subcategoryDescription">Description</label>
                        <textarea id="subcategoryDescription" class="form-control" rows="3" readonly></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveSubcategory">Save Subcategory</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Pass PHP variables to JavaScript -->
    <script>
        var instructorId = <?php echo json_encode($instructor_id); ?>;
        var components = <?php echo json_encode($components); ?>; // Now includes component_id
        var subcategories = <?php echo json_encode($subcategories); ?>;
    </script>
    
    <!-- Revised JavaScript for Grade Management -->
    <script src="/AcadMeter/public/assets/js/grade_management.js"></script>
</body>
</html>
