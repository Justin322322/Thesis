// File: /AcadMeter/public/assets/js/grade_management.js

$(document).ready(function() {
    // Global variables
    let currentQuarter = 1;
    let currentTab = 'summary';
    let studentsData = {}; // Structure: { studentId: { components: { componentId: { grade: Number, subcategories: [{ name: String, score: Number, description: String }] } } } }
    let academicYear = '';
    let componentIdToDetails = {}; // Mapping from component_id to component details

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Fetch sections when the page loads
    fetchSections();

    // Event listeners
    $('#section').change(onSectionChange);
    $('#subject').change(onSubjectChange);
    $('.quarter-tabs').on('click', '.tab-btn', onQuarterChange); // Delegated event
    $('.grade-tabs').on('click', '.grade-tab', onGradeTabChange); // Delegated event
    $(document).on('click', '.add-subcategory-btn', onAddSubcategory); // Delegated event
    $('#saveSubcategory').click(onSaveSubcategory);
    $('#gradeForm').submit(onSaveGrades);
    $('#subcategorySelect').change(updateSubcategoryDescription);
    $(document).on('click', '.remove-subcategory-btn', function() {
        const componentId = $(this).data('component-id');
        const subcategoryName = $(this).data('subcategory-name');
        const studentId = $(this).closest('tr').find('.student-name').data('student-id');
    
        // Confirm removal
        if (confirm(`Are you sure you want to remove the subcategory "${subcategoryName}"?`)) {
            // Remove subcategory from the data structure
            if (studentsData[studentId] && studentsData[studentId].components[componentId]) {
                studentsData[studentId].components[componentId].subcategories = studentsData[studentId].components[componentId].subcategories.filter(sub => sub.name !== subcategoryName);
                
                // Recalculate component grade if necessary
                const componentData = studentsData[studentId].components[componentId];
                if (componentData.subcategories.length > 0) {
                    const total = componentData.subcategories.reduce((sum, sub) => sum + (parseFloat(sub.score) || 0), 0);
                    componentData.grade = Math.round((total / componentData.subcategories.length) * 100) / 100;
                } else {
                    componentData.grade = 0;
                }
    
                // Update the table
                renderGradeTable();
            }
        }
    });

    // Fetch sections
    function fetchSections() {
        ajaxRequest('fetch_sections', { instructor_id: instructorId }, populateSections);
    }

    // Fetch subjects for the selected section
    function fetchSubjects(sectionId) {
        ajaxRequest('fetch_subjects', { section_id: sectionId }, populateSubjects);
    }

    // Fetch students and grades
    function fetchStudentsAndGrades() {
        const sectionId = $('#section').val();
        const subjectId = $('#subject').val();

        if (!sectionId || !subjectId) {
            showError('Please select both a section and a subject before fetching grades.');
            return;
        }

        ajaxRequest('fetch_grades', { section_id: sectionId, subject_id: subjectId, quarter: currentQuarter }, populateGrades);
    }

    // Helper function for AJAX requests
    function ajaxRequest(action, data, successCallback) {
        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            method: 'POST',
            data: { action, ...data },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    successCallback(response);
                } else {
                    showError('Error: ' + response.message);
                }
            },
            error: handleAjaxError
        });
    }

    // Populate sections dropdown
    function populateSections(response) {
        populateDropdown('#section', response.sections, 'section_id', 'section_name', 'school_year');
    }

    // Populate subjects dropdown
    function populateSubjects(response) {
        populateDropdown('#subject', response.subjects, 'subject_id', 'subject_name');
        $('#subject').prop('disabled', false);
    }

    // Populate grades data
    function populateGrades(response) {
        if (response.grades && response.grades.grades) {
            studentsData = response.grades.grades;
            academicYear = response.grades.academic_year;
            componentIdToDetails = response.components;
            renderGradeTable();
        } else {
            showError('No student data received from the server.');
        }
    }

    // Populate dropdown helper function
    function populateDropdown(selector, data, valueKey, textKey, yearKey = null) {
        const dropdown = $(selector);
        dropdown.empty().append('<option value="">-- Select --</option>');
        $.each(data, function(index, item) {
            let optionText = item[textKey];
            if (yearKey && item[yearKey]) optionText += ` (${item[yearKey]})`;
            dropdown.append($('<option></option>').attr('value', item[valueKey]).text(optionText));
        });
    }

    // Event handlers
    function onSectionChange() {
        const sectionId = $(this).val();
        if (sectionId) {
            fetchSubjects(sectionId);
        } else {
            resetSubjectAndTables();
        }
    }

    function onSubjectChange() {
        if ($(this).val()) {
            fetchStudentsAndGrades();
        } else {
            resetTables();
        }
    }

    function onQuarterChange() {
        currentQuarter = $(this).data('quarter');
        updateActiveTab('.quarter-tabs .tab-btn', this);
        fetchStudentsAndGrades();
    }

    function onGradeTabChange() {
        currentTab = $(this).data('tab');
        updateActiveTab('.grade-tabs .grade-tab', this);
        $('.tab-content').removeClass('active');
        $(`#${currentTab}-tab`).addClass('active');
        renderGradeTable();
    }

    function onAddSubcategory() {
        const componentId = $(this).data('component-id');
        $('#modalComponentId').val(componentId);
        populateSubcategorySelect(componentId);
        $('#subcategoryModal').modal('show');
    }

    function onSaveSubcategory() {
        const componentId = parseInt($('#modalComponentId').val());
        const subcategoryName = $('#subcategorySelect').val();
        const subcategoryDescription = $('#subcategoryDescription').val();

        if (!subcategoryName) {
            showError('Please select a subcategory.');
            return;
        }

        addSubcategoryToStudents(componentId, subcategoryName, subcategoryDescription);
        renderGradeTable();
        $('#subcategoryModal').modal('hide');
    }

    function onSaveGrades(e) {
        e.preventDefault();
        const sectionId = $('#section').val();
        const subjectId = $('#subject').val();
        const grades = collectGradesData();

        ajaxRequest('save_grades', {
            section_id: sectionId,
            subject_id: subjectId,
            quarter: currentQuarter,
            academic_year: academicYear,
            grades: JSON.stringify(grades)
        }, function(response) {
            showSuccess('Grades saved successfully');
            fetchStudentsAndGrades();
        });
    }

    // Helper functions
    function renderGradeTable() {
        const tableBody = currentTab === 'summary' ? $('#gradeTableBody') : $('#detailedGradeTableBody');
        tableBody.empty();

        if (Object.keys(studentsData).length === 0) {
            tableBody.append('<tr><td colspan="100%">No students found for this section and subject.</td></tr>');
            return;
        }

        $.each(studentsData, function(studentId, studentData) {
            const row = $('<tr></tr>');
            row.append($('<td></td>').text(studentData.student_name).addClass('student-name').attr('data-student-id', studentId));

            $.each(components, function(index, component) {
                const componentGrade = studentData.components[component.component_id] || { grade: '', subcategories: [] };
                const cell = $('<td></td>').addClass('grade-cell');

                if (currentTab === 'summary') {
                    cell.append(createGradeInput(studentId, component.component_id, componentGrade.grade));
                } else {
                    cell.append(createComponentTotal(component, componentGrade.grade));
                    cell.append(createSubcategoriesContainer(studentId, component.component_id, componentGrade.subcategories));
                }

                row.append(cell);
            });

            row.append(createGradeColumns(studentData.components));
            tableBody.append(row);
        });
    }

    function createGradeInput(studentId, componentId, grade) {
        return $('<input>')
            .addClass('form-control grade-input')
            .attr({
                type: 'number',
                min: '0',
                max: '100',
                step: '0.01',
                'data-student-id': studentId,
                'data-component-id': componentId
            })
            .val(grade)
            .on('input', updateGrades);
    }

    function createComponentTotal(component, grade) {
        return $('<div></div>')
            .addClass('component-total')
            .text(`${component.name} Total: ${grade || '0'}`);
    }

    function createSubcategoriesContainer(studentId, componentId, subcategories) {
        const container = $('<div></div>').addClass('subcategories');
        $.each(subcategories, function(index, subcategory) {
            container.append(createSubcategoryRow(studentId, componentId, subcategory));
        });
        return container;
    }

    function createSubcategoryRow(studentId, componentId, subcategory) {
        const row = $('<div></div>').addClass('subcategory-row');
        row.append($('<span></span>').addClass('subcategory-name').text(subcategory.name).attr('title', subcategory.description));
        row.append($('<input>')
            .addClass('form-control subcategory-score')
            .attr({
                type: 'number',
                min: '0',
                max: '100',
                step: '0.01',
                'data-student-id': studentId,
                'data-component-id': componentId,
                'data-subcategory': subcategory.name
            })
            .val(subcategory.score)
            .on('input', updateSubcategoryScore));
        // Replace the text 'Remove' with a minus icon
        row.append($('<button></button>')
            .addClass('btn btn-danger btn-sm remove-subcategory-btn')
            .attr({
                'data-component-id': componentId,
                'data-subcategory-name': subcategory.name
            })
            .html('<i class="fas fa-minus"></i>')); // Changed from .text('Remove')
        return row;
    }

    function createGradeColumns(componentsData) {
        const initialGrade = calculateInitialGrade(componentsData);
        const quarterlyGrade = calculateQuarterlyGrade(componentsData);
        const remarks = getRemarks(quarterlyGrade);

        return [
            $('<td></td>').addClass('initial-grade').text(initialGrade.toFixed(2)),
            $('<td></td>').addClass('quarterly-grade').text(quarterlyGrade),
            $('<td></td>').addClass('remarks').text(remarks).addClass(quarterlyGrade >= 75 ? 'text-success' : 'text-danger')
        ];
    }

    function updateGrades() {
        const studentId = $(this).data('student-id');
        const componentId = $(this).data('component-id');
        const grade = parseFloat($(this).val()) || 0;

        updateStudentComponentGrade(studentId, componentId, grade);
        updateStudentTotals(studentId);
    }

    function updateSubcategoryScore() {
        const studentId = $(this).data('student-id');
        const componentId = $(this).data('component-id');
        const subcategoryName = $(this).data('subcategory');
        const score = parseFloat($(this).val()) || 0;

        updateStudentSubcategoryScore(studentId, componentId, subcategoryName, score);
        updateComponentTotal(studentId, componentId);
        updateStudentTotals(studentId);
    }

    function updateStudentComponentGrade(studentId, componentId, grade) {
        if (!studentsData[studentId]) {
            studentsData[studentId] = { components: {} };
        }
        if (!studentsData[studentId].components[componentId]) {
            studentsData[studentId].components[componentId] = { grade: 0, subcategories: [] };
        }
        studentsData[studentId].components[componentId].grade = grade;
    }

    function updateStudentSubcategoryScore(studentId, componentId, subcategoryName, score) {
        if (!studentsData[studentId].components[componentId]) {
            studentsData[studentId].components[componentId] = { grade: 0, subcategories: [] };
        }

        const subcategory = studentsData[studentId].components[componentId].subcategories.find(s => s.name === subcategoryName);
        if (subcategory) {
            subcategory.score = score;
        } else {
            studentsData[studentId].components[componentId].subcategories.push({ name: subcategoryName, score: score, description: '' });
        }
    }

    function updateComponentTotal(studentId, componentId) {
        const componentData = studentsData[studentId].components[componentId];
        if (componentData && componentData.subcategories.length > 0) {
            const total = componentData.subcategories.reduce((sum, sub) => sum + (parseFloat(sub.score) || 0), 0) / componentData.subcategories.length;
            componentData.grade = Math.round(total * 100) / 100;

            updateTableCell(studentId, componentId, componentData.grade);
            updateStudentTotals(studentId);
        }
    }

    function updateTableCell(studentId, componentId, grade) {
        updateTableCellInTab('#detailedGradeTableBody', studentId, componentId, grade);
        updateTableCellInTab('#gradeTableBody', studentId, componentId, grade);
    }

    function updateTableCellInTab(tableSelector, studentId, componentId, grade) {
        $(`${tableSelector} tr`).each(function() {
            const currentRow = $(this);
            const rowStudentId = currentRow.find('.student-name').data('student-id');
            if (rowStudentId === parseInt(studentId)) {
                currentRow.find('.grade-cell input[data-component-id="' + componentId + '"]').val(grade);
            }
        });
    }

    function updateStudentTotals(studentId) {
        const studentComponents = studentsData[studentId].components;
        const initialGrade = calculateInitialGrade(studentComponents);
        const quarterlyGrade = calculateQuarterlyGrade(studentComponents);
        const remarks = getRemarks(quarterlyGrade);

        updateStudentTotalsInTab('#gradeTableBody', studentId, initialGrade, quarterlyGrade, remarks);
        updateStudentTotalsInTab('#detailedGradeTableBody', studentId, initialGrade, quarterlyGrade, remarks);
    }

    function updateStudentTotalsInTab(tableSelector, studentId, initialGrade, quarterlyGrade, remarks) {
        $(`${tableSelector} tr`).each(function() {
            const currentRow = $(this);
            const rowStudentId = currentRow.find('.student-name').data('student-id');
            if (rowStudentId === parseInt(studentId)) {
                currentRow.find('.initial-grade').text(initialGrade.toFixed(2));
                currentRow.find('.quarterly-grade').text(quarterlyGrade);
                currentRow.find('.remarks')
                    .text(remarks)
                    .removeClass('text-success text-danger')
                    .addClass(quarterlyGrade >= 75 ? 'text-success' : 'text-danger');
            }
        });
    }

    function calculateInitialGrade(componentsData) {
        let finalGrade = 0;
        let totalWeight = 0;

        $.each(components, function(index, component) {
            const componentData = componentsData[component.component_id];
            if (componentData) {
                finalGrade += (parseFloat(componentData.grade) || 0) * (parseFloat(component.weight) || 0);
                totalWeight += parseFloat(component.weight) || 0;
            }
        });

        if (totalWeight === 0) return 0;

        return Math.min(Math.round((finalGrade / totalWeight) * 100) / 100, 100);
    }

    function calculateQuarterlyGrade(componentsData) {
        return Math.min(Math.round(calculateInitialGrade(componentsData)), 100);
    }

    function getRemarks(grade) {
        if (grade >= 90) return 'Outstanding (O)';
        if (grade >= 85) return 'Very Satisfactory (VS)';
        if (grade >= 80) return 'Satisfactory (S)';
        if (grade >= 75) return 'Fairly Satisfactory (FS)';
        return 'Did Not Meet Expectations (DME)';
    }

    function collectGradesData() {
        const grades = {};
        $.each(studentsData, function(studentId, studentGrades) {
            grades[studentId] = {};
            $.each(components, function(index, component) {
                if (studentGrades.components[component.component_id]) {
                    grades[studentId][component.component_id] = {
                        grade: studentGrades.components[component.component_id].grade,
                        subcategories: studentGrades.components[component.component_id].subcategories
                    };
                }
            });
        });
        return grades;
    }

    function populateSubcategorySelect(componentId) {
        const subcategorySelect = $('#subcategorySelect');
        subcategorySelect.empty().append('<option value="">-- Select Subcategory --</option>');
        if (subcategories[componentId]) {
            subcategories[componentId].forEach(function(subcategory) {
                subcategorySelect.append($('<option></option>')
                    .attr('value', subcategory.name)
                    .text(subcategory.name)
                    .data('description', subcategory.description));
            });
            updateSubcategoryDescription();
        } else {
            subcategorySelect.append('<option value="">No subcategories available</option>');
            $('#subcategoryDescription').val('');
        }
    }

    function updateSubcategoryDescription() {
        const selectedOption = $('#subcategorySelect option:selected');
        const description = selectedOption.data('description') || '';
        $('#subcategoryDescription').val(description);
    }

    function showError(message) {
        console.error(message);
        const alertDiv = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">')
            .text(message)
            .append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
        $('#grade-management').prepend(alertDiv);

        // Automatically hide the alert after 5 seconds
        setTimeout(() => {
            alertDiv.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }

    function showSuccess(message) {
        console.log(message);
        const alertDiv = $('<div class="alert alert-success alert-dismissible fade show" role="alert">')
            .text(message)
            .append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
        $('#grade-management').prepend(alertDiv);
    }

    function handleAjaxError(xhr, status, error) {
        console.error('AJAX Error:', status, error);
        console.error('Response:', xhr.responseText);
        showError(`An error occurred: ${status}, ${error}. Please check the console for more details.`);
    }

    function resetSubjectAndTables() {
        $('#subject').prop('disabled', true).empty().append('<option value="">-- Select Subject --</option>');
        resetTables();
    }

    function resetTables() {
        $('#gradeTableBody, #detailedGradeTableBody').empty().append('<tr><td colspan="100%">No data available.</td></tr>');
    }

    function updateActiveTab(tabSelector, activeElement) {
        $(tabSelector).removeClass('active');
        $(activeElement).addClass('active');
    }

    function addSubcategoryToStudents(componentId, subcategoryName, subcategoryDescription) {
        $.each(studentsData, function(studentId, studentGrades) {
            if (!studentGrades.components[componentId]) {
                studentGrades.components[componentId] = { grade: 0, subcategories: [] };
            }
            const existing = studentGrades.components[componentId].subcategories.find(s => s.name === subcategoryName);
            if (!existing) {
                // ...existing code...
                studentGrades.components[componentId].subcategories.push({
                    name: subcategoryName,
                    description: subcategoryDescription,
                    score: 0
                });
                // ...existing code...
            }
        });
    }
});
