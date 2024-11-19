// File: public/assets/js/grade_management.js

$(document).ready(function() {
    let currentQuarter = '1st';
    let currentTab = 'summary';
    let students = [];
    let grades = {};
    let selectedComponent = '';

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Event listeners
    $('.quarter-tabs .tab-btn').on('click', handleQuarterChange);
    $('.grade-tabs .grade-tab').on('click', handleTabChange);
    $('#section').on('change', handleSectionChange);
    $('#subject').on('change', handleSubjectChange);
    $('#gradeForm').on('submit', handleGradeFormSubmit);
    $(document).on('click', '.add-subcategory-btn', handleAddSubcategory);
    $('#saveSubcategory').on('click', handleSaveSubcategory);
    $('#subcategorySelect').on('change', handleSubcategorySelectChange);

    // Initial data load
    fetchSections();

    function handleQuarterChange() {
        currentQuarter = $(this).data('quarter');
        $('.quarter-tabs .tab-btn').removeClass('active');
        $(this).addClass('active');
        fetchGrades();
    }

    function handleTabChange() {
        currentTab = $(this).data('tab');
        $('.grade-tabs .grade-tab').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $(`#${currentTab}-tab`).addClass('active');
        renderGradeTable();
    }

    function handleSectionChange() {
        const sectionId = $(this).val();
        if (sectionId) {
            fetchSubjects(sectionId);
            $('#subject').prop('disabled', false);
        } else {
            $('#subject').prop('disabled', true).html('<option value="">-- Select Subject --</option>');
        }
    }

    function handleSubjectChange() {
        fetchStudents();
        fetchGrades();
    }

    function handleGradeFormSubmit(e) {
        e.preventDefault();
        saveGrades();
    }

    function handleAddSubcategory() {
        selectedComponent = $(this).data('component');
        openSubcategoryModal();
    }

    function handleSaveSubcategory() {
        saveSubcategory();
    }

    function handleSubcategorySelectChange() {
        const selectedSubcategory = $(this).val();
        const description = subcategories[selectedComponent].find(subcat => subcat.name === selectedSubcategory).description;
        $('#subcategoryDescription').val(description);
    }

    function fetchSections() {
        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            type: 'POST',
            data: {
                action: 'fetch_sections',
                instructor_id: instructorId
            },
            dataType: 'json',
            success: handleFetchSectionsSuccess,
            error: handleAjaxError
        });
    }

    function handleFetchSectionsSuccess(response) {
        if (response.status === 'success') {
            $('#section').empty().append('<option value="">-- Select Section --</option>');
            response.sections.forEach(function(section) {
                $('#section').append(`<option value="${section.section_id}">${section.section_name}</option>`);
            });
        } else {
            console.error('Failed to fetch sections:', response.message);
        }
    }

    function fetchSubjects(sectionId) {
        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            type: 'POST',
            data: {
                action: 'fetch_subjects',
                section_id: sectionId
            },
            dataType: 'json',
            success: handleFetchSubjectsSuccess,
            error: handleAjaxError
        });
    }

    function handleFetchSubjectsSuccess(response) {
        if (response.status === 'success') {
            $('#subject').empty().append('<option value="">-- Select Subject --</option>');
            response.subjects.forEach(function(subject) {
                $('#subject').append(`<option value="${subject.subject_id}">${subject.subject_name}</option>`);
            });
        } else {
            console.error('Failed to fetch subjects:', response.message);
        }
    }

    function fetchStudents() {
        const sectionId = $('#section').val();
        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            type: 'POST',
            data: {
                action: 'fetch_students',
                section_id: sectionId
            },
            dataType: 'json',
            success: handleFetchStudentsSuccess,
            error: handleAjaxError
        });
    }

    function handleFetchStudentsSuccess(response) {
        if (response.status === 'success') {
            students = response.students;
            renderGradeTable();
        } else {
            console.error('Failed to fetch students:', response.message);
        }
    }

    function fetchGrades() {
        const sectionId = $('#section').val();
        const subjectId = $('#subject').val();
        if (!sectionId || !subjectId) return;

        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            type: 'POST',
            data: {
                action: 'fetch_grades',
                section_id: sectionId,
                subject_id: subjectId,
                quarter: currentQuarter
            },
            dataType: 'json',
            success: handleFetchGradesSuccess,
            error: handleAjaxError
        });
    }

    function handleFetchGradesSuccess(response) {
        if (response.status === 'success') {
            grades = response.grades;
            renderGradeTable();
        } else {
            console.error('Failed to fetch grades:', response.message);
        }
    }

    function renderGradeTable() {
        const tableBody = currentTab === 'summary' ? $('#gradeTableBody') : $('#detailedGradeTableBody');
        tableBody.empty();

        students.forEach(function(student) {
            const studentGrades = grades[student.student_id] || {};
            const initialGrade = calculateInitialGrade(studentGrades);
            const quarterlyGrade = calculateQuarterlyGrade(initialGrade);
            const remarks = getRemarks(quarterlyGrade);

            let row = `<tr><td class="student-name">${student.student_name}</td>`;

            components.forEach(function(component) {
                if (currentTab === 'summary') {
                    row += renderSummaryCell(student, component, studentGrades);
                } else {
                    row += renderDetailedCell(student, component, studentGrades);
                }
            });

            row += `<td class="initial-grade">${initialGrade.toFixed(2)}</td>`;
            row += `<td class="quarterly-grade ${quarterlyGrade === 'Failed' ? 'failed-grade' : ''}">${quarterlyGrade}</td>`;
            row += `<td class="remarks ${remarks === 'Failed' ? 'failed-grade' : 'passed-grade'}">${remarks}</td></tr>`;

            tableBody.append(row);
        });

        // Re-attach event listeners
        $('.grade-input, .subcategory-score').off('change').on('change', updateGrade);

        // Initialize tooltips after rendering
        initializeTooltips();
    }

    function renderSummaryCell(student, component, studentGrades) {
        return `<td>
            <input type="number" class="form-control grade-input" 
                data-student-id="${student.student_id}" 
                data-component="${component.key}" 
                value="${studentGrades[component.key]?.grade || ''}" 
                min="0" max="100" step="0.01"
                title="Total score for all ${component.name.toLowerCase()}">
        </td>`;
    }

    function renderDetailedCell(student, component, studentGrades) {
        let cell = `<td class="grade-cell">
            <div class="component-total" title="Average of all ${component.name.toLowerCase()} activities">
                ${studentGrades[component.key]?.grade || '0.00'}
            </div>
            <div class="subcategories">`;

        if (studentGrades[component.key]?.subcategories) {
            studentGrades[component.key].subcategories.forEach(function(subcat, index) {
                cell += `<div class="subcategory-row">
                    <span class="subcategory-name" title="${subcat.description}">${subcat.name}</span>
                    <input type="number" class="form-control subcategory-score" 
                        data-student-id="${student.student_id}" 
                        data-component="${component.key}"
                        data-subcategory-index="${index}"
                        value="${subcat.grade || ''}" 
                        min="0" max="100" step="0.01"
                        title="Score for ${subcat.name}">
                </div>`;
            });
        }

        cell += `</div></td>`;

        return cell;
    }

    function calculateInitialGrade(studentGrades) {
        return components.reduce(function(total, component) {
            return total + (studentGrades[component.key]?.grade || 0) * (component.weight / 100);
        }, 0);
    }

    function calculateQuarterlyGrade(initialGrade) {
        return initialGrade >= 75 ? Math.round(initialGrade) : 'Failed';
    }

    function getRemarks(quarterlyGrade) {
        return quarterlyGrade === 'Failed' ? 'Failed' : 'Passed';
    }

    function updateGrade() {
        const studentId = $(this).data('student-id');
        const component = $(this).data('component');
        const value = parseFloat($(this).val()) || 0;

        if (!grades[studentId]) {
            grades[studentId] = {};
        }
        if (!grades[studentId][component]) {
            grades[studentId][component] = { grade: 0, subcategories: [] };
        }

        if ($(this).hasClass('subcategory-score')) {
            const subcategoryIndex = $(this).data('subcategory-index');
            grades[studentId][component].subcategories[subcategoryIndex].grade = value;
            // Recalculate the main component grade
            grades[studentId][component].grade = calculateAverageSubcategoryGrade(grades[studentId][component].subcategories);
        } else {
            grades[studentId][component].grade = value;
        }

        renderGradeTable();
    }

    function calculateAverageSubcategoryGrade(subcategories) {
        const total = subcategories.reduce((sum, subcat) => sum + (subcat.grade || 0), 0);
        return subcategories.length > 0 ? total / subcategories.length : 0;
    }

    function openSubcategoryModal() {
        $('#componentSelect').val(selectedComponent);
        updateSubcategorySelect();
        $('#subcategoryModal').modal('show');
    }

    function updateSubcategorySelect() {
        $('#subcategorySelect').empty();
        subcategories[selectedComponent].forEach(function(subcat) {
            $('#subcategorySelect').append(`<option value="${subcat.name}">${subcat.name}</option>`);
        });
        $('#subcategoryDescription').val(subcategories[selectedComponent][0].description);
    }

    function saveSubcategory() {
        const subcategoryName = $('#subcategorySelect').val();
        const description = $('#subcategoryDescription').val();

        students.forEach(function(student) {
            if (!grades[student.student_id]) {
                grades[student.student_id] = {};
            }
            if (!grades[student.student_id][selectedComponent]) {
                grades[student.student_id][selectedComponent] = { grade: 0, subcategories: [] };
            }
            grades[student.student_id][selectedComponent].subcategories.push({
                name: subcategoryName,
                description: description,
                grade: 0
            });
        });

        $('#subcategoryModal').modal('hide');
        renderGradeTable();
    }

    function saveGrades() {
        const sectionId = $('#section').val();
        const subjectId = $('#subject').val();

        if (!sectionId || !subjectId) {
            alert('Please select both a section and a subject before saving grades.');
            return;
        }

        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            type: 'POST',
            data: {
                action: 'save_grades',
                grades: JSON.stringify(grades),
                detailed_grades: JSON.stringify(grades),
                section_id: sectionId,
                subject_id: subjectId,
                quarter: currentQuarter
            },
            dataType: 'json',
            success: handleSaveGradesSuccess,
            error: handleAjaxError
        });
    }

    function handleSaveGradesSuccess(response) {
        if (response.status === 'success') {
            alert('Grades saved successfully!');
        } else {
            console.error('Failed to save grades:', response.message);
            alert('Failed to save grades. Please try again.');
        }
    }

    function handleAjaxError(jqXHR, textStatus, errorThrown) {
        console.error('AJAX error:', textStatus, errorThrown);
        console.log('Full response:', jqXHR.responseText);
        alert('An unexpected error occurred. Please check the console for more details.');
    }

    function initializeTooltips() {
        $('[title]').tooltip({
            placement: 'top',
            trigger: 'hover'
        });
    }
});

console.log('Grade Management JS Initialized');