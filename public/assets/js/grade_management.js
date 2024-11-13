// C:\xampp\htdocs\AcadMeter\public\assets\js\grade_management.js

/**
 * Function to display Bootstrap alerts
 * @param {string} message - The alert message
 * @param {string} type - The type of alert (success, danger, warning, info)
 */
function showAlert(message, type = 'success') {
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    $('#alertPlaceholder').html(alertHTML);
}

/**
 * Function to filter options in a select element based on search input
 * @param {string} selectId - The ID of the select element
 * @param {string} searchId - The ID of the search input
 */
function filterOptions(selectId, searchId) {
    const searchInput = document.getElementById(searchId).value.toLowerCase();
    const select = document.getElementById(selectId);
    const options = select.getElementsByTagName('option');

    for (let i = 0; i < options.length; i++) {
        const optionText = options[i].textContent.toLowerCase();
        if (optionText.includes(searchInput)) {
            options[i].style.display = '';
        } else {
            options[i].style.display = 'none';
        }
    }
}

$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Handle CSV Upload
    $('#uploadCSV').on('click', function() {
        const fileInput = $('#csvFile')[0];
        if (fileInput.files.length === 0) {
            showAlert('Please select a CSV file to upload.', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'upload_csv');
        formData.append('csvFile', fileInput.files[0]);
        formData.append('csrf_token', $('input[name="csrf_token"]').val());

        // Disable the button to prevent multiple uploads
        $('#uploadCSV').prop('disabled', true).text('Uploading...');

        $.ajax({
            url: '/AcadMeter/server/controllers/teacher_dashboard_controller.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    // Optionally, refresh the page or update the roster dynamically
                    location.reload();
                } else {
                    showAlert(response.message || 'Failed to upload and process CSV.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error uploading CSV:', error);
                showAlert('An error occurred while uploading the CSV.', 'danger');
            },
            complete: function() {
                // Re-enable the button
                $('#uploadCSV').prop('disabled', false).text('Upload & Process CSV');
                // Clear the file input
                $('#csvFile').val('');
            }
        });
    });

    // Handle Add Subject Form Submission
    $('#addSubjectForm').on('submit', function(event) {
        event.preventDefault();
        const subjectName = $('#newSubjectName').val().trim();
        const csrfToken = $('input[name="csrf_token"]').val();

        if (!subjectName) {
            showAlert('Please enter a subject name.', 'warning');
            return;
        }

        // Disable the button to prevent multiple submissions
        $('#addSubjectForm button[type="submit"]').prop('disabled', true);

        // Send AJAX request to add subject
        $.ajax({
            url: '/AcadMeter/server/controllers/teacher_dashboard_controller.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'add_subject',
                subject_name: subjectName,
                csrf_token: csrfToken
            }),
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    // Append the new subject to the subject selection dropdowns
                    $('#subjectSelect').append(new Option(response.subject_name, response.subject_id));
                    // Append to the Manage Subjects table
                    $('#manageSubjectsTable tbody').append(`
                        <tr id="subjectRow${response.subject_id}">
                            <td>${response.subject_id}</td>
                            <td id="subjectName${response.subject_id}">${response.subject_name}</td>
                            <td class="action-buttons">
                                <button class="btn btn-sm btn-info edit-subject" data-subject-id="${response.subject_id}" data-subject-name="${response.subject_name}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger delete-subject" data-subject-id="${response.subject_id}">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    `);
                    // Clear the input field
                    $('#newSubjectName').val('');
                    // Hide the modal
                    $('#addSubjectModal').modal('hide');
                } else {
                    showAlert(response.message || 'Failed to add subject.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error adding subject:', error);
                showAlert('An error occurred while adding the subject.', 'danger');
            },
            complete: function() {
                // Re-enable the button
                $('#addSubjectForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Handle Edit Subject Button Click
    $(document).on('click', '.edit-subject', function() {
        const subjectId = $(this).data('subject-id');
        const subjectName = $(this).data('subject-name');

        // Populate the modal fields
        $('#editSubjectId').val(subjectId);
        $('#editSubjectName').val(subjectName);

        // Show the modal
        $('#editSubjectModal').modal('show');
    });

    // Handle Edit Subject Form Submission
    $('#editSubjectForm').on('submit', function(event) {
        event.preventDefault();
        const subjectId = $('#editSubjectId').val();
        const subjectName = $('#editSubjectName').val().trim();
        const csrfToken = $('input[name="csrf_token"]').val();

        if (!subjectName) {
            showAlert('Subject name cannot be empty.', 'warning');
            return;
        }

        // Disable the submit button to prevent multiple submissions
        $('#editSubjectForm button[type="submit"]').prop('disabled', true);

        // Send AJAX request to update subject
        $.ajax({
            url: '/AcadMeter/server/controllers/teacher_dashboard_controller.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'update_subject',
                subject_id: subjectId,
                subject_name: subjectName,
                csrf_token: csrfToken
            }),
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    // Update the subject name in the subject selection dropdown
                    $(`#subjectSelect option[value="${subjectId}"]`).text(response.subject_name);
                    // Update the subject name in the Manage Subjects table
                    $(`#subjectName${subjectId}`).text(response.subject_name);
                    // Update data attributes on the edit button
                    $(`button.edit-subject[data-subject-id="${subjectId}"]`).data('subject-name', response.subject_name);
                    // Hide the modal
                    $('#editSubjectModal').modal('hide');
                } else {
                    showAlert(response.message || 'Failed to update subject.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating subject:', error);
                showAlert('An error occurred while updating the subject.', 'danger');
            },
            complete: function() {
                // Re-enable the submit button
                $('#editSubjectForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Handle Delete Subject Button Click
    $(document).on('click', '.delete-subject', function() {
        const subjectId = $(this).data('subject-id');
        const subjectName = $(`#subjectName${subjectId}`).text();

        // Populate the modal fields
        $('#deleteSubjectId').val(subjectId);
        $('#deleteSubjectName').text(subjectName);

        // Show the modal
        $('#deleteSubjectModal').modal('show');
    });

    // Handle Delete Subject Form Submission
    $('#deleteSubjectForm').on('submit', function(event) {
        event.preventDefault();
        const subjectId = $('#deleteSubjectId').val();
        const csrfToken = $('input[name="csrf_token"]').val();

        // Disable the submit button to prevent multiple submissions
        $('#deleteSubjectForm button[type="submit"]').prop('disabled', true);

        // Send AJAX request to delete subject
        $.ajax({
            url: '/AcadMeter/server/controllers/teacher_dashboard_controller.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'delete_subject',
                subject_id: subjectId,
                csrf_token: csrfToken
            }),
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    // Remove the subject from the subject selection dropdown
                    $(`#subjectSelect option[value="${subjectId}"]`).remove();
                    // Remove the subject row from the Manage Subjects table
                    $(`#subjectRow${subjectId}`).remove();
                    // Optionally, remove the subject from other select elements if applicable
                    // Hide the modal
                    $('#deleteSubjectModal').modal('hide');
                } else {
                    showAlert(response.message || 'Failed to delete subject.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error deleting subject:', error);
                showAlert('An error occurred while deleting the subject.', 'danger');
            },
            complete: function() {
                // Re-enable the submit button
                $('#deleteSubjectForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Handle Dynamic Row Additions and Removals

    // Function to add a new row
    function addRow(sectionId, type) {
        let placeholderText = '';
        let rowHTML = '';

        switch(type) {
            case 'quiz':
                placeholderText = `Quiz ${$(`#${sectionId}QuizGrades tr`).length + 1}`;
                rowHTML = `
                    <tr>
                        <td><input type="text" class="form-control" placeholder="${placeholderText}" required></td>
                        <td><input type="number" class="form-control score" placeholder="Score" min="0" required></td>
                        <td><input type="number" class="form-control items" placeholder="Total Items" min="1" required></td>
                        <td><input type="number" class="form-control weight" placeholder="Weight (%)" min="0" max="100" required></td>
                        <td><span class="weighted-grade">0%</span></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
                    </tr>
                `;
                break;
            case 'assignment':
                placeholderText = `Assignment ${$(`#${sectionId}AssignmentGrades tr`).length + 1}`;
                rowHTML = `
                    <tr>
                        <td><input type="text" class="form-control" placeholder="${placeholderText}" required></td>
                        <td><input type="number" class="form-control score" placeholder="Score" min="0" required></td>
                        <td><input type="number" class="form-control items" placeholder="Total Items" min="1" required></td>
                        <td><input type="number" class="form-control weight" placeholder="Weight (%)" min="0" max="100" required></td>
                        <td><span class="weighted-grade">0%</span></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
                    </tr>
                `;
                break;
            case 'extracurricular':
                placeholderText = `Activity ${$(`#${sectionId}ExtracurricularGrades tr`).length + 1}`;
                rowHTML = `
                    <tr>
                        <td><input type="text" class="form-control" placeholder="${placeholderText}" required></td>
                        <td><input type="number" class="form-control score" placeholder="Score" min="0" required></td>
                        <td><input type="number" class="form-control items" placeholder="Total Items" min="1" required></td>
                        <td><span class="weighted-grade">0%</span></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
                    </tr>
                `;
                break;
            default:
                console.error('Unknown type for addRow:', type);
                return;
        }

        $(`#${sectionId}${type.charAt(0).toUpperCase() + type.slice(1)}Grades`).append(rowHTML);
    }

    // Add Quiz Row
    $('.add-quiz-row').on('click', function() {
        const quarter = $(this).data('quarter');
        const sectionId = `quarter${quarter}`;
        addRow(sectionId, 'quiz');
    });

    // Add Assignment Row
    $('.add-assignment-row').on('click', function() {
        const quarter = $(this).data('quarter');
        const sectionId = `quarter${quarter}`;
        addRow(sectionId, 'assignment');
    });

    // Add Extracurricular Activity Row
    $('.add-extracurricular-row').on('click', function() {
        const quarter = $(this).data('quarter');
        const sectionId = `quarter${quarter}`;
        addRow(sectionId, 'extracurricular');
    });

    // Remove Row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Compute Weighted Grades for Quizzes, Assignments, Extracurriculars, and Exams
    $('.compute-grade').on('click', function() {
        const quarter = $(this).data('quarter');
        const sectionId = `quarter${quarter}`;
        let totalWeightedGrade = 0;

        // Compute Quizzes
        $(`#${sectionId}QuizGrades tr`).each(function() {
            const score = parseFloat($(this).find('.score').val()) || 0;
            const items = parseFloat($(this).find('.items').val()) || 1;
            const weight = parseFloat($(this).find('.weight').val()) || 0;
            const grade = (score / items) * weight;
            $(this).find('.weighted-grade').text(`${grade.toFixed(2)}%`);
            totalWeightedGrade += grade;
        });

        // Compute Assignments
        $(`#${sectionId}AssignmentGrades tr`).each(function() {
            const score = parseFloat($(this).find('.score').val()) || 0;
            const items = parseFloat($(this).find('.items').val()) || 1;
            const weight = parseFloat($(this).find('.weight').val()) || 0;
            const grade = (score / items) * weight;
            $(this).find('.weighted-grade').text(`${grade.toFixed(2)}%`);
            totalWeightedGrade += grade;
        });

        // Compute Extracurriculars
        $(`#${sectionId}ExtracurricularGrades tr`).each(function() {
            const score = parseFloat($(this).find('.score').val()) || 0;
            const items = parseFloat($(this).find('.items').val()) || 1;
            const grade = (score / items) * 100; // Assuming weight is 100% for extracurriculars
            $(this).find('.weighted-grade').text(`${grade.toFixed(2)}%`);
            totalWeightedGrade += grade;
        });

        // Compute Exams
        const midtermScore = parseFloat($(`#${sectionId} .score-midterm`).val()) || 0;
        const midtermItems = parseFloat($(`#${sectionId} .items-midterm`).val()) || 1;
        const midtermWeight = parseFloat($(`#${sectionId} .weight-midterm`).val()) || 0;
        const midtermGrade = (midtermScore / midtermItems) * midtermWeight;
        $(`#${sectionId} .weighted-grade-midterm`).text(`${midtermGrade.toFixed(2)}%`);
        totalWeightedGrade += midtermGrade;

        const finalScore = parseFloat($(`#${sectionId} .score-final`).val()) || 0;
        const finalItems = parseFloat($(`#${sectionId} .items-final`).val()) || 1;
        const finalWeight = parseFloat($(`#${sectionId} .weight-final`).val()) || 0;
        const finalGrade = (finalScore / finalItems) * finalWeight;
        $(`#${sectionId} .weighted-grade-final`).text(`${finalGrade.toFixed(2)}%`);
        totalWeightedGrade += finalGrade;

        // Display Total Weighted Grade
        showAlert(`Quarter ${quarter} Total Weighted Grade: ${totalWeightedGrade.toFixed(2)}%`, 'info');
    });
});
