// File: /AcadMeter/public/assets/js/grade_management.js

$(document).ready(function() {
    let currentQuarter = 1;
    let currentTab = 'summary';
    let students = [];
    let grades = {};
    let selectedComponent = '';

    // Event listeners
    $('.quarter-tabs .tab-btn').on('click', handleQuarterChange);
    $('.grade-tabs .grade-tab').on('click', handleTabChange);
    $('#section').on('change', handleSectionChange);
    $('#subject').on('change', handleSubjectChange);
    $('#gradeForm').on('submit', function(e) {
        e.preventDefault();
        if (validateForm()) {
            saveGrades();
        }
    });
    $(document).on('click', '.add-subcategory-btn', handleAddSubcategory);
    $('#saveSubcategory').on('click', handleSaveSubcategory);
    $('#subcategorySelect').on('change', handleSubcategorySelectChange);
    $(document).on('click', '.remove-subcategory-btn', handleRemoveSubcategory);
    $('#section, #subject, #academic_year').on('change input', updateSaveButtonState);

    // Initialize tooltips
    initializeTooltips();

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

    function handleRemoveSubcategory() {
        const studentId = $(this).data('student-id');
        const component = $(this).data('component');
        const subcategoryIndex = $(this).data('subcategory-index');

        if (confirm('Are you sure you want to remove this subcategory?')) {
            grades[studentId][component].subcategories.splice(subcategoryIndex, 1);
            
            // Recalculate the main component grade
            grades[studentId][component].grade = calculateAverageSubcategoryGrade(grades[studentId][component].subcategories);

            renderGradeTable();
        }
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
        const academicYear = $('#academic_year').val();
        if (!sectionId || !subjectId || !academicYear) return;

        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            type: 'POST',
            data: {
                action: 'fetch_grades',
                section_id: sectionId,
                subject_id: subjectId,
                quarter: currentQuarter,
                academic_year: academicYear
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

    function saveGrades() {
        const sectionId = $('#section').val();
        const subjectId = $('#subject').val();
        const academicYear = $('#academic_year').val();

        $('.save-grades').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            type: 'POST',
            data: {
                action: 'save_grades',
                section_id: sectionId,
                subject_id: subjectId,
                quarter: currentQuarter,
                academic_year: academicYear,
                grades: JSON.stringify(grades)
            },
            dataType: 'json',
            success: handleSaveGradesSuccess,
            error: handleAjaxError
        });
    }

    function handleSaveGradesSuccess(response) {
        $('.save-grades').prop('disabled', false).html('<i class="fas fa-save"></i> Save Grades');
        if (response.status === 'success') {
            alert('Grades saved successfully!');
            // Lock the saved grades
            Object.keys(grades).forEach(studentId => {
                Object.keys(grades[studentId]).forEach(component => {
                    grades[studentId][component].locked = true;
                });
            });
            renderGradeTable();
        } else {
            console.error('Failed to save grades:', response.message);
            alert('Failed to save grades. Please try again.');
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
            row += `<td class="quarterly-grade ${quarterlyGrade < 75 ? 'failed-grade' : ''}">${quarterlyGrade}</td>`;
            row += `<td class="remarks ${remarks === 'Failed' ? 'failed-grade' : 'passed-grade'}">${remarks}</td></tr>`;

            tableBody.append(row);
        });

        initializeTooltips();
        attachGradeInputListeners();
    }

    function renderSummaryCell(student, component, studentGrades) {
        const componentId = components.findIndex(c => c.key === component.key) + 1;
        const isLocked = studentGrades[componentId]?.locked || false;
        return `<td>
            <input type="number" class="form-control grade-input" 
                data-student-id="${student.student_id}" 
                data-component="${componentId}" 
                value="${studentGrades[componentId]?.grade || ''}" 
                min="0" max="100" step="0.01"
                title="Total score for all ${component.name.toLowerCase()}"
                ${isLocked ? 'disabled' : ''}
                style="${isLocked ? 'background-color: #e9ecef;' : ''}">
        </td>`;
    }

    function renderDetailedCell(student, component, studentGrades) {
        const componentId = components.findIndex(c => c.key === component.key) + 1;
        const isLocked = studentGrades[componentId]?.locked || false;
        let cell = `<td class="grade-cell">
            <div class="component-total" title="Average of all ${component.name.toLowerCase()} activities">
                ${studentGrades[componentId]?.grade || '0.00'}
            </div>
            <div class="subcategories">`;

        if (studentGrades[componentId]?.subcategories) {
            studentGrades[componentId].subcategories.forEach(function(subcat, index) {
                cell += `<div class="subcategory-row">
                    <span class="subcategory-name" title="${subcat.description}">${subcat.name}</span>
                    <input type="number" class="form-control subcategory-score" 
                        data-student-id="${student.student_id}" 
                        data-component="${componentId}"
                        data-subcategory-index="${index}"
                        value="${subcat.grade || ''}" 
                        min="0" max="100" step="0.01"
                        title="Score for ${subcat.name}"
                        ${isLocked ? 'disabled' : ''}
                        style="${isLocked ? 'background-color: #e9ecef;' : ''}">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-subcategory-btn"
                        data-student-id="${student.student_id}"
                        data-component="${componentId}"
                        data-subcategory-index="${index}"
                        ${isLocked ? 'disabled' : ''}>
                        <i class="fas fa-minus"></i>
                    </button>
                </div>`;
            });
        }

        cell += `</div></td>`;

        return cell;
    }

    function attachGradeInputListeners() {
        $('.grade-input, .subcategory-score').off('input').on('input', updateGrade);
    }

    function updateGrade() {
        const studentId = $(this).data('student-id');
        const component = $(this).data('component');
        const value = parseFloat($(this).val()) || 0;

        if (!grades[studentId]) {
            grades[studentId] = {};
        }
        if (!grades[studentId][component]) {
            grades[studentId][component] = { grade: 0, subcategories: [], locked: false };
        }

        if (grades[studentId][component].locked) {
            alert('This grade is locked and cannot be modified.');
            $(this).val(grades[studentId][component].grade);
            return;
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

    function calculateInitialGrade(studentGrades) {
        let totalWeightedGrade = 0;
        let totalWeight = 0;

        components.forEach(function(component, index) {
            const componentId = index + 1;
            const grade = studentGrades[componentId]?.grade || 0;
            totalWeightedGrade += grade * (component.weight / 100);
            totalWeight += component.weight / 100;
        });

        return totalWeight > 0 ? totalWeightedGrade / totalWeight : 0;
    }

    function calculateQuarterlyGrade(initialGrade) {
        return Math.round(initialGrade);
    }

    function getRemarks(quarterlyGrade) {
        return quarterlyGrade >= 75 ? 'Passed' : 'Failed';
    }

    function calculateAverageSubcategoryGrade(subcategories) {
        if (subcategories.length === 0) return 0;
        const totalGrade = subcategories.reduce((sum, subcat) => sum + (subcat.grade || 0), 0);
        return totalGrade / subcategories.length;
    }

    function openSubcategoryModal() {
        $('#componentSelect').val(selectedComponent);
        populateSubcategorySelect();
        $('#subcategoryModal').modal('show');
    }

    function populateSubcategorySelect() {
        const $subcategorySelect = $('#subcategorySelect');
        $subcategorySelect.empty();
        subcategories[selectedComponent].forEach(function(subcat) {
            $subcategorySelect.append(`<option value="${subcat.name}">${subcat.name}</option>`);
        });
        $subcategorySelect.trigger('change');
    }

    function saveSubcategory() {
        const subcategoryName = $('#subcategorySelect').val();
        const subcategoryDescription = $('#subcategoryDescription').val();

        students.forEach(function(student) {
            if (!grades[student.student_id]) {
                grades[student.student_id] = {};
            }
            const componentId = components.findIndex(c => c.key === selectedComponent) + 1;
            if (!grades[student.student_id][componentId]) {
                grades[student.student_id][componentId] = { grade: 0, subcategories: [], locked: false };
            }
            grades[student.student_id][componentId].subcategories.push({
                name: subcategoryName,
                description: subcategoryDescription,
                grade: 0
            });
        });

        $('#subcategoryModal').modal('hide');
        renderGradeTable();
    }

    function initializeTooltips() {
        $('[data-toggle="tooltip"]').tooltip({
            trigger: 'click',
            placement: 'top'
        }).on('click', function (e) {
            e.preventDefault();
            $(this).tooltip('toggle');
        });

        // Hide tooltip when clicking outside
        $(document).on('click', function (e) {
            if ($(e.target).data('toggle') !== 'tooltip' && $(e.target).parents('.tooltip').length === 0) {
                $('[data-toggle="tooltip"]').tooltip('hide');
            }
        });
    }

    function validateForm() {
        const sectionId = $('#section').val();
        const subjectId = $('#subject').val();
        const academicYear = $('#academic_year').val();

        if (!sectionId || !subjectId || !academicYear) {
            alert('Please select a section, subject, and enter the academic year.');
            return false;
        }

        if (!academicYear.match(/^\d{4}-\d{4}$/)) {
            alert('Please enter a valid academic year in the format YYYY-YYYY.');
            return false;
        }

        return true;
    }

    function updateSaveButtonState() {
        const sectionId = $('#section').val();
        const subjectId = $('#subject').val();
        const academicYear = $('#academic_year').val();
        $('.save-grades').prop('disabled', !(sectionId && subjectId && academicYear));
    }

    function handleAjaxError(xhr, status, error) {
        console.error('AJAX Error:', status, error);
        alert('An error occurred while processing your request. Please try again.');
    }
});