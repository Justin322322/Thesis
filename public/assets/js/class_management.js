// File: C:\xampp\htdocs\AcadMeter\public\assets\js\class_management.js

document.addEventListener('DOMContentLoaded', () => {
    console.log('Class Management JS Initialized');

    // Function to show modal messages (success, fail, warning)
    function showModalMessage(title, message, type) {
        const modalId = 'messageModal';
        let modal = document.getElementById(modalId);
        
        if (!modal) {
            // Create modal if it doesn't exist
            modal = document.createElement('div');
            modal.id = modalId;
            modal.className = 'modal fade';
            modal.setAttribute('tabindex', '-1');
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-labelledby', 'messageModalLabel');
            modal.setAttribute('aria-hidden', 'true');
            
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="messageModalLabel"></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
        
        const modalTitle = modal.querySelector('.modal-title');
        const modalBody = modal.querySelector('.modal-body');
        const modalContent = modal.querySelector('.modal-content');
        
        modalTitle.textContent = title;
        modalBody.textContent = message;
        
        // Reset classes
        modalContent.className = 'modal-content';
        
        // Add appropriate class based on type
        if (type === 'success') {
            modalContent.classList.add('modal-success');
        } else if (type === 'fail' || type === 'error') {
            modalContent.classList.add('modal-danger');
        } else if (type === 'warning') {
            modalContent.classList.add('modal-warning');
        }
        
        $(modal).modal('show');
    }

    // Assign Student to Section
    const assignStudentForm = document.getElementById('assignStudentForm');
    if (assignStudentForm) {
        assignStudentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!assignStudentForm.checkValidity()) {
                assignStudentForm.classList.add('was-validated');
                return;
            }
            const formData = new FormData(assignStudentForm);

            fetch('/AcadMeter/server/controllers/assign_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showModalMessage('Success', data.message, 'success');
                    assignStudentForm.reset();
                    assignStudentForm.classList.remove('was-validated');
                    // Remove the assigned student from the available_students select
                    const assignedStudentId = formData.get('student_id');
                    const studentSelect = document.getElementById('studentSelect');
                    const option = studentSelect.querySelector(`option[value="${assignedStudentId}"]`);
                    if (option) {
                        option.remove();
                    }
                } else {
                    showModalMessage('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showModalMessage('Error', 'An unexpected error occurred.', 'error');
            });
        });
    }

    // Assign Subject to Section
    const assignSubjectForm = document.getElementById('assignSubjectForm');
    if (assignSubjectForm) {
        assignSubjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!assignSubjectForm.checkValidity()) {
                assignSubjectForm.classList.add('was-validated');
                return;
            }
            const formData = new FormData(assignSubjectForm);

            fetch('/AcadMeter/server/controllers/assign_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showModalMessage('Success', data.message, 'success');
                    assignSubjectForm.reset();
                    assignSubjectForm.classList.remove('was-validated');
                    // Optionally, update the sections or subjects list
                } else {
                    showModalMessage('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showModalMessage('Error', 'An unexpected error occurred.', 'error');
            });
        });
    }

    // Add New Subject
    const addSubjectForm = document.getElementById('addSubjectForm');
    if (addSubjectForm) {
        addSubjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!addSubjectForm.checkValidity()) {
                addSubjectForm.classList.add('was-validated');
                return;
            }
            const formData = new FormData(addSubjectForm);

            fetch('/AcadMeter/server/controllers/add_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    addSubjectForm.reset();
                    addSubjectForm.classList.remove('was-validated');
                    // Append the new subject to the Manage Subjects table
                    appendSubjectToTable(data.subject_id, data.subject_name);
                    // Add the new subject to the subject selects
                    addNewOptionToSelect('subjectSelect', data.subject_id, data.subject_name);
                    addNewOptionToSelect('sectionSubjectSelect', data.subject_id, data.subject_name);
                    showModalMessage('Success', data.message, 'success');
                } else {
                    showModalMessage('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showModalMessage('Error', 'An unexpected error occurred.', 'error');
            });
        });
    }

    // Edit Subject Modal
    const editSubjectModal = $('#editSubjectModal');
    const editSubjectFormModal = document.getElementById('editSubjectFormModal');

    // Function to populate edit subject modal
    function populateEditSubjectModal(subjectId, subjectName) {
        document.getElementById('editSubjectId').value = subjectId;
        document.getElementById('editSubjectName').value = subjectName;
        editSubjectFormModal.classList.remove('was-validated');
        editSubjectModal.modal('show');
    }

    // Event delegation for edit subject buttons
    document.querySelector('#manageSubjectsTable').addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-subject') || e.target.closest('.edit-subject')) {
            const button = e.target.classList.contains('edit-subject') ? e.target : e.target.closest('.edit-subject');
            const subjectId = button.getAttribute('data-subject-id');
            const subjectName = button.getAttribute('data-subject-name');
            populateEditSubjectModal(subjectId, subjectName);
        }
    });

    if (editSubjectFormModal) {
        editSubjectFormModal.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!editSubjectFormModal.checkValidity()) {
                editSubjectFormModal.classList.add('was-validated');
                return;
            }
            const formData = new FormData(editSubjectFormModal);

            fetch('/AcadMeter/server/controllers/edit_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update the subject row in the table
                    const subjectRow = document.getElementById(`subjectRow${data.subject_id}`);
                    if (subjectRow) {
                        const subjectNameCell = document.getElementById(`subjectName${data.subject_id}`);
                        if (subjectNameCell) {
                            subjectNameCell.textContent = data.subject_name;
                        }
                        // Update data attributes for edit and delete buttons
                        const editButton = subjectRow.querySelector('.edit-subject');
                        const deleteButton = subjectRow.querySelector('.delete-subject');
                        if (editButton) editButton.setAttribute('data-subject-name', data.subject_name);
                        if (deleteButton) deleteButton.setAttribute('data-subject-name', data.subject_name);
                    }
                    editSubjectModal.modal('hide');
                    showModalMessage('Success', data.message, 'success');
                } else {
                    showModalMessage('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showModalMessage('Error', 'An unexpected error occurred.', 'error');
            });
        });
    }

    // Delete Subject Modal
    const deleteSubjectModal = $('#deleteSubjectModal');
    const deleteSubjectFormModal = document.getElementById('deleteSubjectFormModal');

    // Event delegation for delete subject buttons
    document.querySelector('#manageSubjectsTable').addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-subject') || e.target.closest('.delete-subject')) {
            const button = e.target.classList.contains('delete-subject') ? e.target : e.target.closest('.delete-subject');
            const subjectId = button.getAttribute('data-subject-id');
            const subjectName = button.getAttribute('data-subject-name');
            
            document.getElementById('deleteSubjectId').value = subjectId;
            document.getElementById('deleteSubjectName').textContent = subjectName;
            deleteSubjectFormModal.classList.remove('was-validated');
            deleteSubjectModal.modal('show');
        }
    });

    if (deleteSubjectFormModal) {
        deleteSubjectFormModal.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!deleteSubjectFormModal.checkValidity()) {
                deleteSubjectFormModal.classList.add('was-validated');
                return;
            }
            const formData = new FormData(deleteSubjectFormModal);
            const subjectId = formData.get('subject_id');

            // Remove the subject row from the table immediately
            const subjectRow = document.getElementById(`subjectRow${subjectId}`);
            if (subjectRow) {
                subjectRow.remove();
            }

            // Remove the subject from the select elements
            removeOptionFromSelect('subjectSelect', subjectId);
            removeOptionFromSelect('sectionSubjectSelect', subjectId);

            // Close the modal
            deleteSubjectModal.modal('hide');

            // Send the delete request to the server
            fetch('/AcadMeter/server/controllers/delete_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showModalMessage('Success', data.message, 'success');
                } else {
                    showModalMessage('Error', data.message, 'error');
                    // If deletion failed on the server, revert the UI changes
                    appendSubjectToTable(subjectId, document.getElementById('deleteSubjectName').textContent);
                    addNewOptionToSelect('subjectSelect', subjectId, document.getElementById('deleteSubjectName').textContent);
                    addNewOptionToSelect('sectionSubjectSelect', subjectId, document.getElementById('deleteSubjectName').textContent);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showModalMessage('Error', 'An unexpected error occurred.', 'error');
                // If there was an error, revert the UI changes
                appendSubjectToTable(subjectId, document.getElementById('deleteSubjectName').textContent);
                addNewOptionToSelect('subjectSelect', subjectId, document.getElementById('deleteSubjectName').textContent);
                addNewOptionToSelect('sectionSubjectSelect', subjectId, document.getElementById('deleteSubjectName').textContent);
            });
        });
    }

    // Function to append a new subject to the table
    function appendSubjectToTable(subjectId, subjectName) {
        const tbody = document.querySelector('#manageSubjectsTable tbody');
        const row = document.createElement('tr');
        row.id = `subjectRow${subjectId}`;
        
        row.innerHTML = `
            <td>${subjectId}</td>
            <td id="subjectName${subjectId}">${subjectName}</td>
            <td class="text-center">
                <button class="btn btn-info btn-sm edit-subject me-2" data-subject-id="${subjectId}" data-subject-name="${subjectName}">
                    <i class="fas fa-edit"></i> <span>Edit</span>
                </button>
                <button class="btn btn-danger btn-sm delete-subject" data-subject-id="${subjectId}" data-subject-name="${subjectName}">
                    <i class="fas fa-trash-alt"></i> <span>Delete</span>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    }

    // Function to add a new option to select elements
    function addNewOptionToSelect(selectId, value, text) {
        const select = document.getElementById(selectId);
        if (select) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = text;
            select.appendChild(option);
        }
    }

    // Function to remove an option from select elements
    function removeOptionFromSelect(selectId, value) {
        const select = document.getElementById(selectId);
        if (select) {
            const option = select.querySelector(`option[value="${value}"]`);
            if (option) {
                option.remove();
            }
        }
    }
});