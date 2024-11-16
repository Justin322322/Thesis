// File: C:\xampp\htdocs\AcadMeter\public\assets\js\class_management.js

/**
 * Function to display a Bootstrap modal with dynamic content
 * @param {string} title - The modal title
 * @param {string} message - The modal body content
 * @param {string} type - The type of message (success, danger, warning, info)
 */
function showModal(title, message, type = 'info') {
    // Set modal header color based on the type
    const headerClass = {
        'success': 'bg-success text-white',
        'danger': 'bg-danger text-white',
        'warning': 'bg-warning text-dark',
        'info': 'bg-info text-white'
    };

    $('#messageModalHeader').attr('class', 'modal-header ' + headerClass[type]);
    $('#messageModalLabel').text(title);
    $('#messageModalBody').html(message);

    // Show the modal
    $('#messageModal').modal('show');
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
            showModal('Warning', 'Please select a section.', 'warning');
            return;
        }

        if (!students || students.length === 0) {
            showModal('Warning', 'Please select at least one student to assign.', 'warning');
            return;
        }

        // Disable the submit button to prevent multiple submissions
        $('#assignStudentsForm button[type="submit"]').prop('disabled', true);

        // Send AJAX request to assign students
        $.ajax({
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            data: {
                action: 'assign_students_to_section',
                section_id: sectionId,
                students: students,
                csrf_token: csrfToken
            },
            dataType: 'json',
            traditional: true, // Needed when sending arrays via $.ajax
            success: function(response) {
                if (response.status === 'success') {
                    showModal('Success', response.message, 'success');
                    // Optionally, refresh the page to reflect changes
                    location.reload();
                } else {
                    showModal('Error', response.message || 'Failed to assign students to section.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error assigning students:', error);
                showModal('Error', 'An error occurred while assigning students.', 'danger');
            },
            complete: function() {
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
            showModal('Warning', 'Please select a section.', 'warning');
            return;
        }

        if (!subjectId) {
            showModal('Warning', 'Please select a subject to assign.', 'warning');
            return;
        }

        $('#assignSubjectForm button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            data: {
                action: 'assign_subject_to_section',
                section_id: sectionId,
                subject_id: subjectId,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showModal('Success', response.message, 'success');
                    // Optionally, refresh the page to reflect changes
                    location.reload();
                } else {
                    showModal('Error', response.message || 'Failed to assign subject to section.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error assigning subject:', error);
                showModal('Error', 'An error occurred while assigning the subject.', 'danger');
            },
            complete: function() {
                $('#assignSubjectForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Handle Add New Subject
    $('#addSubjectForm').on('submit', function(event) {
        event.preventDefault();
        const subjectName = $('#newSubject').val().trim();
        const csrfToken = $('input[name="csrf_token"]').val();

        if (!subjectName) {
            showModal('Warning', 'Please enter a subject name.', 'warning');
            return;
        }

        $('#addSubjectForm button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            data: {
                action: 'add_subject',
                subject_name: subjectName,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showModal('Success', `The subject "${response.subject_name}" has been added successfully.`, 'success');
                    // Update the subjects list
                    $('#assignSubjectSelect').append(
                        $('<option>', {
                            value: response.subject_id,
                            text: response.subject_name
                        })
                    );
                    $('#manageSubjectsTable tbody').append(`
                        <tr id="subjectRow${response.subject_id}">
                            <td>${response.subject_id}</td>
                            <td id="subjectName${response.subject_id}">${response.subject_name}</td>
                            <td>
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
                    $('#newSubject').val('');
                } else {
                    showModal('Error', response.message || 'Failed to add subject.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error adding subject:', error);
                showModal('Error', 'An error occurred while adding the subject.', 'danger');
            },
            complete: function() {
                $('#addSubjectForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Handle Edit Subject Button Click
    $(document).on('click', '.edit-subject', function() {
        const subjectId = $(this).data('subject-id');
        const subjectName = $(this).data('subject-name');
        $('#editSubjectId').val(subjectId);
        $('#editSubjectName').val(subjectName);
        $('#editSubjectModal').modal('show');
    });

    // Handle Edit Subject Form Submission
    $('#editSubjectForm').on('submit', function(event) {
        event.preventDefault();
        const subjectId = $('#editSubjectId').val();
        const subjectName = $('#editSubjectName').val().trim();
        const csrfToken = $('input[name="csrf_token"]').val();

        if (!subjectName) {
            showModal('Warning', 'Subject name cannot be empty.', 'warning');
            return;
        }

        $('#editSubjectForm button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            data: {
                action: 'update_subject',
                subject_id: subjectId,
                subject_name: subjectName,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showModal('Success', response.message, 'success');
                    // Update the subjects list
                    $(`#subjectName${subjectId}`).text(response.subject_name);
                    $(`#assignSubjectSelect option[value="${subjectId}"]`).text(response.subject_name);
                    $('#editSubjectModal').modal('hide');
                } else {
                    showModal('Error', response.message || 'Failed to update subject.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating subject:', error);
                showModal('Error', 'An error occurred while updating the subject.', 'danger');
            },
            complete: function() {
                $('#editSubjectForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Handle Delete Subject Button Click
    $(document).on('click', '.delete-subject', function() {
        const subjectId = $(this).data('subject-id');
        const subjectName = $(`#subjectName${subjectId}`).text();
        $('#deleteSubjectId').val(subjectId);
        $('#deleteSubjectName').text(subjectName);
        $('#deleteSubjectModal').modal('show');
    });

    // Handle Delete Subject Form Submission
    $('#deleteSubjectForm').on('submit', function(event) {
        event.preventDefault();
        const subjectId = $('#deleteSubjectId').val();
        const csrfToken = $('input[name="csrf_token"]').val();

        // Send AJAX request to delete the subject
        $.ajax({
            url: '/AcadMeter/server/controllers/class_management_controller.php',
            type: 'POST',
            data: {
                action: 'delete_subject',
                subject_id: subjectId,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Display success pop-up
                    showModal('Success', response.message, 'success');
                    // Update UI accordingly
                    $('#subjectRow' + subjectId).remove();
                    // Optionally, remove the subject from the assign subject select dropdown
                    $(`#assignSubjectSelect option[value="${subjectId}"]`).remove();
                    // Hide the delete modal
                    $('#deleteSubjectModal').modal('hide');
                } else {
                    // Display error pop-up
                    showModal('Error', response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showModal('Error', 'An unexpected error occurred.', 'danger');
            }
        });
    });
});
