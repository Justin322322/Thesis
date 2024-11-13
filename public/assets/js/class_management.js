// C:\xampp\htdocs\AcadMeter\public\assets\js\class_management.js

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

    // Handle Assign Students to Section
    $('#assignStudentsForm').on('submit', function(event) {
        event.preventDefault();
        const sectionId = $('#assignSectionSelect').val();
        const students = $('#assignStudentSelect').val();
        const csrfToken = $('input[name="csrf_token"]').val();

        if (!sectionId) {
            showAlert('Please select a section.', 'warning');
            return;
        }

        if (!students || students.length === 0) {
            showAlert('Please select at least one student to assign.', 'warning');
            return;
        }

        // Disable the submit button to prevent multiple submissions
        $('#assignStudentsForm button[type="submit"]').prop('disabled', true);

        // Send AJAX request to assign students
        $.ajax({
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'assign_students_to_section',
                section_id: sectionId,
                students: students,
                csrf_token: csrfToken
            }),
            dataType: 'json', // Ensure the response is treated as JSON
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message + '<br>' + response.already_assigned, 'success');
                    // Optionally, refresh the page or update the roster dynamically
                    location.reload();
                } else {
                    showAlert(response.message || 'Failed to assign students to section.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error assigning students:', error);
                showAlert('An error occurred while assigning students.', 'danger');
            },
            complete: function() {
                // Re-enable the submit button
                $('#assignStudentsForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Handle Assign Subject to Section
    $('#assignSubjectForm').on('submit', function(event) {
        event.preventDefault();
        const sectionId = $('#assignSubjectSectionSelect').val();
        const subjectId = $('#assignSubjectSelect').val();
        const csrfToken = $('input[name="csrf_token"]').val();

        if (!sectionId) {
            showAlert('Please select a section.', 'warning');
            return;
        }

        if (!subjectId) {
            showAlert('Please select a subject to assign.', 'warning');
            return;
        }

        // Disable the submit button to prevent multiple submissions
        $('#assignSubjectForm button[type="submit"]').prop('disabled', true);

        // Send AJAX request to assign subject
        $.ajax({
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'assign_subject_to_section',
                section_id: sectionId,
                subject_id: subjectId,
                csrf_token: csrfToken
            }),
            dataType: 'json', // Ensure the response is treated as JSON
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    // Optionally, refresh the page or update the roster dynamically
                    location.reload();
                } else {
                    showAlert(response.message || 'Failed to assign subject to section.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error assigning subject:', error);
                showAlert('An error occurred while assigning the subject.', 'danger');
            },
            complete: function() {
                // Re-enable the submit button
                $('#assignSubjectForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Handle Add New Subject
    $('#addSubjectButton').on('click', function() {
        const subjectName = $('#newSubject').val().trim();
        const csrfToken = $('input[name="csrf_token"]').val();

        if (!subjectName) {
            showAlert('Please enter a subject name.', 'warning');
            return;
        }

        // Disable the button to prevent multiple submissions
        $('#addSubjectButton').prop('disabled', true);

        // Send AJAX request to add subject
        $.ajax({
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'add_subject',
                subject_name: subjectName,
                csrf_token: csrfToken
            }),
            dataType: 'json', // Ensure the response is treated as JSON
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    // Append the new subject to the subject selection dropdowns
                    $('#assignSubjectSelect').append(new Option(response.subject_name, response.subject_id));
                    $('#assignSubjectSelect').val(response.subject_id); // Optionally select the new subject
                    $('#newSubject').val('');
                    // Also append to the Manage Subjects table
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
                $('#addSubjectButton').prop('disabled', false);
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
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'update_subject',
                subject_id: subjectId,
                subject_name: subjectName,
                csrf_token: csrfToken
            }),
            dataType: 'json', // Ensure the response is treated as JSON
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    // Update the subject name in the Assign Subject dropdown
                    $(`#assignSubjectSelect option[value="${subjectId}"]`).text(response.subject_name);
                    // Update the subject name in the Manage Subjects table
                    $(`#subjectName${subjectId}`).text(response.subject_name);
                    // Update the subject name in the Class Roster headers if assigned
                    $('.collapse').each(function() {
                        const currentSection = $(this);
                        const headerId = currentSection.attr('aria-labelledby');
                        const header = $(`#${headerId}`);
                        const button = header.find('button');
                        // Use regex to replace the old subject name with the new one
                        const regex = new RegExp(`( - Subject: )${response.old_subject_name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`, 'i');
                        const updatedText = button.html().replace(regex, `$1${response.subject_name}`);
                        button.html(updatedText);
                    });
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
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'delete_subject',
                subject_id: subjectId,
                csrf_token: csrfToken
            }),
            dataType: 'json', // Ensure the response is treated as JSON
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    // Remove the subject from the Assign Subject dropdown
                    $(`#assignSubjectSelect option[value="${subjectId}"]`).remove();
                    // Remove the subject row from the Manage Subjects table
                    $(`#subjectRow${subjectId}`).remove();
                    // Update sections that had this subject assigned
                    $('.collapse').each(function() {
                        const currentSection = $(this);
                        const headerId = currentSection.attr('aria-labelledby');
                        const header = $(`#${headerId}`);
                        const button = header.find('button');
                        // Use regex to remove " - Subject: Subject Name" regardless of trailing characters
                        const regex = new RegExp(`( - Subject: )${response.old_subject_name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`, 'i');
                        const updatedText = button.html().replace(regex, ' - No Subject Assigned');
                        button.html(updatedText);
                    });
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
});
