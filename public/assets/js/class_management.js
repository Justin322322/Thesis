document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.getAttribute('data-tab');
            
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            document.getElementById(`${tabName}-tab`).classList.add('active');
        });
    });

    // Existing event listeners
    document.getElementById('addSectionForm').addEventListener('submit', addSection);
    document.getElementById('addSubjectForm').addEventListener('submit', addSubject);
    document.getElementById('assignSubjectForm').addEventListener('submit', assignSubject);
    document.getElementById('assignStudentForm').addEventListener('submit', assignStudent);
    document.getElementById('editSubjectFormModal').addEventListener('submit', editSubject);
    document.getElementById('deleteSubjectFormModal').addEventListener('submit', deleteSubject);
    document.getElementById('deleteSectionFormModal').addEventListener('submit', deleteSection);

    // Change event delegation from document to rosterContainer
    const rosterContainer = document.getElementById('classRosterContainer');
    if (rosterContainer) {
        rosterContainer.addEventListener('click', function(e) {
            const editButton = e.target.closest('.edit-subject');
            if (editButton && rosterContainer.contains(editButton)) {
                e.preventDefault();
                openEditSubjectModal(editButton.dataset.subjectId, editButton.dataset.subjectName);
                return;
            }

            const deleteSubjectButton = e.target.closest('.delete-subject');
            if (deleteSubjectButton && rosterContainer.contains(deleteSubjectButton)) {
                e.preventDefault();
                openDeleteSubjectModal(deleteSubjectButton.dataset.subjectId, deleteSubjectButton.dataset.subjectName);
                return;
            }

            const deleteSectionButton = e.target.closest('.delete-section');
            if (deleteSectionButton && rosterContainer.contains(deleteSectionButton)) {
                e.preventDefault();
                openDeleteSectionModal(deleteSectionButton.dataset.sectionId, deleteSectionButton.dataset.sectionName);
                return;
            }

            const removeSubjectButton = e.target.closest('.remove-subject');
            if (removeSubjectButton && rosterContainer.contains(removeSubjectButton)) {
                e.preventDefault();
                removeSubject(removeSubjectButton.dataset.sectionId, removeSubjectButton.dataset.subjectId);
                return;
            }

            const removeStudentButton = e.target.closest('.remove-student');
            if (removeStudentButton && rosterContainer.contains(removeStudentButton)) {
                e.preventDefault();
                removeStudent(removeStudentButton.dataset.sectionId, removeStudentButton.dataset.studentId);
                return;
            }
        }, false);
    }

    // Add event delegation for subject table
    const subjectsTable = document.querySelector('#manageSubjectsTable');
    if (subjectsTable) {
        subjectsTable.addEventListener('click', function(e) {
            const target = e.target.closest('button');
            if (!target) return;

            e.preventDefault();
            
            if (target.classList.contains('edit-subject')) {
                const subjectId = target.dataset.subjectId;
                const subjectName = target.dataset.subjectName;
                openEditSubjectModal(subjectId, subjectName);
            } else if (target.classList.contains('delete-subject')) {
                const subjectId = target.dataset.subjectId;
                const subjectName = target.dataset.subjectName;
                openDeleteSubjectModal(subjectId, subjectName);
            }
        });
    }

    // Update delete subject form handler
    const deleteSubjectForm = document.getElementById('deleteSubjectFormModal');
    if (deleteSubjectForm) {
        deleteSubjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const subjectId = document.getElementById('deleteSubjectId').value;
            
            fetch('/AcadMeter/server/controllers/class_management_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_subject&subject_id=${subjectId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccess(data.message);
                    loadSubjects();
                    loadClassRoster();
                    $('#deleteSubjectModal').modal('hide');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An unexpected error occurred while deleting the subject.');
            });
        });
    }

    // Update delete section form handler
    const deleteSectionForm = document.getElementById('deleteSectionFormModal');
    if (deleteSectionForm) {
        deleteSectionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const sectionId = document.getElementById('deleteSectionId').value;
            
            fetch('/AcadMeter/server/controllers/class_management_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_section&section_id=${sectionId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccess(data.message);
                    loadSections();
                    loadClassRoster();
                    $('#deleteSectionModal').modal('hide');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An unexpected error occurred while deleting the section.');
            });
        });
    }

    // Remove the previous event listeners to avoid duplicates
    document.getElementById('deleteSubjectFormModal').removeEventListener('submit', deleteSubject);
    document.getElementById('deleteSectionFormModal').removeEventListener('submit', deleteSection);

    // Initial load
    loadSections();
    loadSubjects();
    loadStudents();
    loadClassRoster();

    // Add event listeners for modal auto-hide behavior
    const messageModal = document.getElementById('messageModal');
    if (messageModal) {
        messageModal.addEventListener('mouseenter', function() {
            if (window.modalTimeout) {
                clearTimeout(window.modalTimeout);
            }
        });

        messageModal.addEventListener('mouseleave', function() {
            window.modalTimeout = setTimeout(() => {
                $(messageModal).modal('hide');
            }, 3000);
        });
    }
});

function loadSections() {
    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_sections'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            populateSectionDropdowns(data.sections);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while loading sections. Please try again.');
    });
}

function loadSubjects() {
    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_subjects'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            populateSubjectDropdown(data.subjects);
            updateSubjectTable(data.subjects);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while loading subjects. Please try again.');
    });
}

function loadStudents() {
    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_students'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            populateStudentDropdown(data.students);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while loading students. Please try again.');
    });
}

function loadClassRoster() {
    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_class_roster'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            updateClassRoster(data.sections);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while loading the class roster. Please try again.');
    });
}

function addSection(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'add_section');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccess(data.message);
            loadSections();
            loadClassRoster();
            event.target.reset();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while adding the section. Please try again.');
    });
}

function addSubject(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'add_subject');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccess(data.message);
            loadSubjects();
            event.target.reset();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while adding the subject. Please try again.');
    });
}

function assignSubject(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'assign_subject');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccess(data.message);
            loadClassRoster();
            event.target.reset();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while assigning the subject. Please try again.');
    });
}

function assignStudent(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'assign_student');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccess(data.message);
            loadClassRoster();
            event.target.reset();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while assigning the student. Please try again.');
    });
}

function editSubject(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'edit_subject');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccess(data.message);
            loadSubjects();
            $('#editSubjectModal').modal('hide');
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while editing the subject. Please try again.');
    });
}

function deleteSubject(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'delete_subject');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccess(data.message);
            loadSubjects();
            loadClassRoster();
            $('#deleteSubjectModal').modal('hide');
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while deleting the subject. Please try again.');
    });
}

function deleteSection(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'delete_section');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccess(data.message);
            loadSections();
            loadClassRoster();
            $('#deleteSectionModal').modal('hide');
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An unexpected error occurred while deleting the section. Please try again.');
    });
}

function removeSubject(sectionId, subjectId) {
    $('#removeSubjectModal').modal('show');
    $('#confirmRemoveSubject').off('click').on('click', function() {
        fetch('/AcadMeter/server/controllers/class_management_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove_subject&section_id=${sectionId}&subject_id=${subjectId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccess(data.message);
                loadClassRoster();
            } else {
                showError(data.message);
            }
            $('#removeSubjectModal').modal('hide');
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An unexpected error occurred while removing the subject. Please try again.');
            $('#removeSubjectModal').modal('hide');
        });
    });
}

let isRemovingStudent = false;

function removeStudent(sectionId, studentId) {
    if (isRemovingStudent) return; // Prevent multiple triggers
    isRemovingStudent = true;

    if (confirm('Are you sure you want to remove this student from the section?')) {
        fetch('/AcadMeter/server/controllers/class_management_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove_student&section_id=${sectionId}&student_id=${studentId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccess(data.message);
                loadClassRoster();
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An unexpected error occurred while removing the student. Please try again.');
        })
        .finally(() => {
            isRemovingStudent = false;
        });
    } else {
        isRemovingStudent = false;
    }
}

function populateSectionDropdowns(sections) {
    const sectionDropdowns = document.querySelectorAll('select[name="section_id"]');
    sectionDropdowns.forEach(dropdown => {
        dropdown.innerHTML = '<option value="">-- Select Section --</option>';
        sections.forEach(section => {
            const option = document.createElement('option');
            option.value = section.section_id;
            option.textContent = `${section.section_name} (${section.school_year})`;
            dropdown.appendChild(option);
        });
    });
}

function populateSubjectDropdown(subjects) {
    const subjectDropdowns = document.querySelectorAll('select[name="subject_id"]');
    subjectDropdowns.forEach(dropdown => {
        dropdown.innerHTML = '<option value="">-- Select Subject --</option>';
        subjects.forEach(subject => {
            const option = document.createElement('option');
            option.value = subject.subject_id;
            option.textContent = subject.subject_name;
            dropdown.appendChild(option);
        });
    });
}

function populateStudentDropdown(students) {
    const studentDropdowns = document.querySelectorAll('select[name="student_id"]');
    studentDropdowns.forEach(dropdown => {
        dropdown.innerHTML = '<option value="">-- Select Student --</option>';
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.student_id;
            option.textContent = `${student.first_name} ${student.last_name}`;
            dropdown.appendChild(option);
        });
    });
}

function updateSubjectTable(subjects) {
    const tableBody = document.querySelector('#manageSubjectsTable tbody');
    tableBody.innerHTML = '';
    subjects.forEach(subject => {
        const row = document.createElement('tr');
        row.id = `subjectRow${subject.subject_id}`;
        row.innerHTML = `
            <td>${subject.subject_id}</td>
            <td id="subjectName${subject.subject_id}">${subject.subject_name}</td>
            <td>
                <button class="btn btn-sm btn-primary edit-subject" data-subject-id="${subject.subject_id}" data-subject-name="${subject.subject_name}">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger delete-subject" data-subject-id="${subject.subject_id}" data-subject-name="${subject.subject_name}">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function updateClassRoster(sections) {
    const rosterContainer = document.getElementById('classRosterContainer');
    rosterContainer.innerHTML = '';
    if (!sections || sections.length === 0) {
        rosterContainer.innerHTML = '<p class="text-muted">No class roster data available.</p>';
        return;
    }
    sections.forEach(section => {
        const sectionElement = document.createElement('div');
        sectionElement.className = 'section-roster mb-4';
        sectionElement.innerHTML = `
            <h4>
                ${section.section_name} (${section.school_year})
                <button class="btn btn-sm btn-danger float-right delete-section" data-section-id="${section.section_id}" data-section-name="${section.section_name}">
                    <i class="fas fa-trash-alt"></i> Delete Section
                </button>
            </h4>
            <div class="row">
                <div class="col-md-6">
                    <h5>Subjects</h5>
                    <ul class="list-group">
                        ${section.subjects && section.subjects.length > 0 ? 
                            section.subjects.map(subject => `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${subject.name}
                                    <button class="btn btn-sm btn-outline-danger remove-subject" data-section-id="${section.section_id}" data-subject-id="${subject.id}">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </li>
                            `).join('') : 
                            '<li class="list-group-item">No subjects assigned</li>'
                        }
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Students (${section.students ? section.students.length : 0})</h5>
                    <ul class="list-group">
                        ${section.students && section.students.length > 0 ? 
                            section.students.map(student => `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${student.name}
                                    <button class="btn btn-sm btn-outline-danger remove-student" data-section-id="${section.section_id}" data-student-id="${student.id}">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </li>
                            `).join('') : 
                            '<li class="list-group-item">No students assigned</li>'
                        }
                    </ul>
                </div>
            </div>
        `;
        rosterContainer.appendChild(sectionElement);
    });
}

function openEditSubjectModal(subjectId, subjectName) {
    document.getElementById('editSubjectId').value = subjectId;
    document.getElementById('editSubjectName').value = subjectName;
    $('#editSubjectModal').modal('show');
}

function openDeleteSubjectModal(subjectId, subjectName) {
    document.getElementById('deleteSubjectId').value = subjectId;
    document.getElementById('deleteSubjectName').textContent = subjectName;
    $('#deleteSubjectModal').modal('show');
}

function openDeleteSectionModal(sectionId, sectionName) {
    document.getElementById('deleteSectionId').value = sectionId;
    document.getElementById('deleteSectionName').textContent = sectionName;
    $('#deleteSectionModal').modal('show');
}

function showSuccess(message) {
    showModal('Success', message, 'success');
}

function showError(message) {
    showModal('Error', message, 'danger');
}

function showModal(title, message, type) {
    const modal = document.getElementById('messageModal');
    if (!modal) {
        console.error('Message modal not found');
        alert(`${title}: ${message}`);
        return;
    }
    const modalTitle = modal.querySelector('.modal-title');
    const modalBody = modal.querySelector('.modal-body');
    const modalDialog = modal.querySelector('.modal-dialog');
    
    if (modalTitle) modalTitle.textContent = title;
    if (modalBody) modalBody.textContent = message;
    if (modalDialog) {
        modalDialog.classList.remove('modal-danger', 'modal-success');
        modalDialog.classList.add(`modal-${type}`);
    }
    
    // Ensure the close button is visible
    const closeButton = modal.querySelector('.close');
    if (closeButton) {
        closeButton.style.display = 'block';
    }
    
    $(modal).modal('show');
    
    // Clear any existing timeout
    if (window.modalTimeout) {
        clearTimeout(window.modalTimeout);
    }
    
    // Set a new timeout
    window.modalTimeout = setTimeout(() => {
        $(modal).modal('hide');
    }, 3000);
}