document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabs = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabId = tab.getAttribute('data-tab');
            
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            tab.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });

    // Existing event listeners
    document.getElementById('addSectionForm').addEventListener('submit', addSection);
    document.getElementById('assignStudentForm').addEventListener('submit', assignStudent);
    document.getElementById('assignSubjectForm').addEventListener('submit', assignSubject);
    document.getElementById('addSubjectForm').addEventListener('submit', addSubject);

    document.querySelectorAll('.edit-subject').forEach(button => {
        button.addEventListener('click', editSubject);
    });
    document.querySelectorAll('.delete-subject').forEach(button => {
        button.addEventListener('click', deleteSubject);
    });

    document.getElementById('editSubjectFormModal').addEventListener('submit', submitEditSubject);
    document.getElementById('deleteSubjectFormModal').addEventListener('submit', submitDeleteSubject);

    document.querySelectorAll('.remove-student').forEach(button => {
        button.addEventListener('click', removeStudent);
    });
    document.querySelectorAll('.remove-subject').forEach(button => {
        button.addEventListener('click', removeSubject);
    });

    document.querySelectorAll('.delete-section').forEach(button => {
        button.addEventListener('click', deleteSection);
    });

    document.getElementById('deleteSectionFormModal').addEventListener('submit', submitDeleteSection);

    // Load initial data
    loadSections();
    loadSubjects();
});

function addSection(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'add_section');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(handleResponse)
    .then(data => {
        if (data.status === 'success') {
            showMessage(data.message);
            event.target.reset();
            loadSections();
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(handleError);
}

function assignStudent(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'assign_student');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(handleResponse)
    .then(data => {
        if (data.status === 'success') {
            showMessage(data.message);
            event.target.reset();
            loadSections();
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(handleError);
}

function assignSubject(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'assign_subject');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(handleResponse)
    .then(data => {
        if (data.status === 'success') {
            showMessage(data.message);
            event.target.reset();
            loadSections();
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(handleError);
}

function addSubject(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'add_subject');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(handleResponse)
    .then(data => {
        if (data.status === 'success') {
            showMessage(data.message);
            event.target.reset();
            loadSubjects();
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(handleError);
}

function editSubject(event) {
    const button = event.currentTarget;
    const subjectId = button.getAttribute('data-subject-id');
    const subjectName = button.getAttribute('data-subject-name');
    
    document.getElementById('editSubjectId').value = subjectId;
    document.getElementById('editSubjectName').value = subjectName;
    
    $('#editSubjectModal').modal('show');
}

function submitEditSubject(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'edit_subject');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(handleResponse)
    .then(data => {
        if (data.status === 'success') {
            showMessage(data.message);
            $('#editSubjectModal').modal('hide');
            loadSubjects();
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(handleError);
}

function deleteSubject(event) {
    const button = event.currentTarget;
    const subjectId = button.getAttribute('data-subject-id');
    const subjectName = button.getAttribute('data-subject-name');
    
    document.getElementById('deleteSubjectId').value = subjectId;
    document.getElementById('deleteSubjectName').textContent = subjectName;
    
    $('#deleteSubjectModal').modal('show');
}

function submitDeleteSubject(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'delete_subject');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(handleResponse)
    .then(data => {
        if (data.status === 'success') {
            showMessage(data.message);
            $('#deleteSubjectModal').modal('hide');
            loadSubjects();
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(handleError);
}

function removeStudent(event) {
    const button = event.currentTarget;
    const sectionId = button.getAttribute('data-section-id');
    const studentId = button.getAttribute('data-student-id');
    
    if (confirm('Are you sure you want to remove this student from the section?')) {
        const formData = new FormData();
        formData.append('action', 'remove_student');
        formData.append('section_id', sectionId);
        formData.append('student_id', studentId);

        fetch('/AcadMeter/server/controllers/class_management_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(handleResponse)
        .then(data => {
            if (data.status === 'success') {
                showMessage(data.message);
                loadSections();
            } else {
                showMessage(data.message, true);
            }
        })
        .catch(handleError);
    }
}

function removeSubject(event) {
    const button = event.currentTarget;
    const sectionId = button.getAttribute('data-section-id');
    const subjectId = button.getAttribute('data-subject-id');
    
    if (confirm('Are you sure you want to remove this subject from the section?')) {
        const formData = new FormData();
        formData.append('action', 'remove_subject');
        formData.append('section_id', sectionId);
        formData.append('subject_id', subjectId);

        fetch('/AcadMeter/server/controllers/class_management_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(handleResponse)
        .then(data => {
            if (data.status === 'success') {
                showMessage(data.message);
                loadSections();
            } else {
                showMessage(data.message, true);
            }
        })
        .catch(handleError);
    }
}

function deleteSection(event) {
    const button = event.currentTarget;
    const sectionId = button.getAttribute('data-section-id');
    const sectionName = button.getAttribute('data-section-name');
    
    document.getElementById('deleteSectionId').value = sectionId;
    document.getElementById('deleteSectionName').textContent = sectionName;
    
    $('#deleteSectionModal').modal('show');
}

function submitDeleteSection(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'delete_section');

    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(handleResponse)
    .then(data => {
        if (data.status === 'success') {
            showMessage(data.message);
            $('#deleteSectionModal').modal('hide');
            loadSections();
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(handleError);
}

function loadSections() {
    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: new URLSearchParams({
            'action': 'get_sections'
        })
    })
    .then(handleResponse)
    .then(data => {
        if (data.status === 'success') {
            updateSectionDropdowns(data.sections);
            updateClassRoster(data.classRoster);
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(handleError);
}

function loadSubjects() {
    fetch('/AcadMeter/server/controllers/class_management_controller.php', {
        method: 'POST',
        body: new URLSearchParams({
            'action': 'get_subjects'
        })
    })
    .then(handleResponse)
    .then(data => {
        if (data.status === 'success') {
            updateSubjectDropdown(data.subjects);
            updateSubjectsTable(data.subjects);
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(handleError);
}

function updateSectionDropdowns(sections) {
    const sectionSelects = document.querySelectorAll('#sectionSelect, #sectionSubjectSelect');
    sectionSelects.forEach(select => {
        select.innerHTML = '<option value="">-- Select Section --</option>';
        sections.forEach(section => {
            const option = document.createElement('option');
            option.value = section.section_id;
            option.textContent = section.section_name;
            select.appendChild(option);
        });
    });
}

function updateSubjectDropdown(subjects) {
    const subjectSelect = document.getElementById('subjectSelect');
    subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
    subjects.forEach(subject => {
        const option = document.createElement('option');
        option.value = subject.subject_id;
        option.textContent = subject.subject_name;
        subjectSelect.appendChild(option);
    });
}

function updateSubjectsTable(subjects) {
    const tbody = document.querySelector('#manageSubjectsTable tbody');
    tbody.innerHTML = '';
    subjects.forEach(subject => {
        const row = `
            <tr id="subjectRow${subject.subject_id}">
                <td>${subject.subject_id}</td>
                <td id="subjectName${subject.subject_id}">${subject.subject_name}</td>
                <td>
                    <button class="btn btn-sm edit-subject" data-subject-id="${subject.subject_id}" data-subject-name="${subject.subject_name}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-subject" data-subject-id="${subject.subject_id}" data-subject-name="${subject.subject_name}">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });

    // Re-attach event listeners
    document.querySelectorAll('.edit-subject').forEach(button => {
        button.addEventListener('click', editSubject);
    });
    document.querySelectorAll('.delete-subject').forEach(button => {
        button.addEventListener('click', deleteSubject);
    });
}

function updateClassRoster(classRoster) {
    const container = document.getElementById('classRosterContainer');
    container.innerHTML = '';

    if (Object.keys(classRoster).length === 0) {
        container.innerHTML = '<p class="text-muted">No class roster data available.</p>';
        return;
    }

    for (const [sectionName, data] of Object.entries(classRoster)) {
        const sectionHtml = `
            <div class="section-roster mb-4">
                <h4>
                    ${sectionName}
                    <button class="btn btn-sm btn-outline-danger float-right delete-section" data-section-id="${data.section_id}" data-section-name="${sectionName}">
                        <i class="fas fa-trash-alt"></i> Delete Section
                    </button>
                </h4>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Subjects</h5>
                        <ul class="list-group">
                            ${data.subjects.length ? data.subjects.map(subject => `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${subject.name}
                                    <button class="btn btn-sm btn-outline-danger remove-subject" data-section="${sectionName}" data-subject="${subject.name}" data-subject-id="${subject.id}" data-section-id="${data.section_id}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </li>
                            `).join('') : '<li class="list-group-item">No subjects assigned</li>'}
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Students</h5>
                        <ul class="list-group">
                            ${data.students.length ? data.students.map(student => `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${student.name}
                                    <button class="btn btn-sm btn-outline-danger remove-student" data-section="${sectionName}" data-student="${student.name}" data-student-id="${student.id}" data-section-id="${data.section_id}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </li>
                            `).join('') : '<li class="list-group-item">No students assigned</li>'}
                        </ul>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', sectionHtml);
    }

    // Re-attach event listeners
    document.querySelectorAll('.delete-section').forEach(button => {
        button.addEventListener('click', deleteSection);
    });
    document.querySelectorAll('.remove-student').forEach(button => {
        button.addEventListener('click', removeStudent);
    });
    document.querySelectorAll('.remove-subject').forEach(button => {
        button.addEventListener('click', removeSubject);
    });
}

function handleResponse(response) {
    if (!response.ok) {
        throw new Error('Network response was not ok');
    }
    return response.json();
}

function handleError(error) {
    console.error('Error:', error);
    showMessage('An error occurred. Please try again.', true);
}

function showMessage(message, isError = false) {
    const alertDiv = document.getElementById('classManagementAlert');
    alertDiv.textContent = message;
    alertDiv.className = isError ? 'alert alert-danger' : 'alert alert-success';
    alertDiv.style.display = 'block';

    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000);
}