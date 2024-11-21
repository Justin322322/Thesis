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

    // Fetch sections
    function fetchSections() {
        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            method: 'POST',
            data: {
                action: 'fetch_sections',
                instructor_id: instructorId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    populateDropdown('#section', response.sections, 'section_id', 'section_name', 'school_year');
                } else {
                    showError('Error fetching sections: ' + response.message);
                }
            },
            error: handleAjaxError
        });
    }

    // Fetch subjects for the selected section
    function fetchSubjects(sectionId) {
        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            method: 'POST',
            data: {
                action: 'fetch_subjects',
                section_id: sectionId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    populateDropdown('#subject', response.subjects, 'subject_id', 'subject_name');
                    $('#subject').prop('disabled', false);
                } else {
                    showError('Error fetching subjects: ' + response.message);
                }
            },
            error: handleAjaxError
        });
    }

    // Fetch students and grades
    function fetchStudentsAndGrades() {
        const sectionId = $('#section').val();
        const subjectId = $('#subject').val();

        if (!sectionId || !subjectId) {
            showError('Please select both a section and a subject before fetching grades.');
            return;
        }

        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            method: 'POST',
            data: {
                action: 'fetch_grades',
                section_id: sectionId,
                subject_id: subjectId,
                quarter: currentQuarter
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    if (response.grades && response.grades.grades) {
                        studentsData = response.grades.grades;
                        academicYear = response.grades.academic_year;
                        componentIdToDetails = response.components; // Ensure this is provided by backend
                        renderGradeTable();
                    } else {
                        showError('No student data received from the server.');
                        console.error('Server response:', response);
                    }
                } else {
                    showError('Error fetching grades: ' + response.message);
                    console.error('Server error:', response);
                }
            },
            error: function(xhr, status, error) {
                showError('Error fetching grades. Please try again.');
                console.error('AJAX error:', status, error);
                console.error('Response:', xhr.responseText);
            }
        });
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
            $('#subject').prop('disabled', true).empty().append('<option value="">-- Select Subject --</option>');
            $('#gradeTableBody, #detailedGradeTableBody').empty().append('<tr><td colspan="100%">No data available.</td></tr>');
        }
    }

    function onSubjectChange() {
        if ($(this).val()) {
            fetchStudentsAndGrades();
        } else {
            $('#gradeTableBody, #detailedGradeTableBody').empty().append('<tr><td colspan="100%">No data available.</td></tr>');
        }
    }

    function onQuarterChange() {
        currentQuarter = $(this).data('quarter');
        $('.quarter-tabs .tab-btn').removeClass('active');
        $(this).addClass('active');
        fetchStudentsAndGrades();
    }

    function onGradeTabChange() {
        currentTab = $(this).data('tab');
        $('.grade-tabs .grade-tab').removeClass('active');
        $(this).addClass('active');
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

        // Add the new subcategory to all students
        $.each(studentsData, function(studentId, studentGrades) {
            if (!studentGrades.components[componentId]) {
                studentGrades.components[componentId] = { grade: 0, subcategories: [] };
            }
            // Check if subcategory already exists
            const existing = studentGrades.components[componentId].subcategories.find(s => s.name === subcategoryName);
            if (!existing) {
                studentGrades.components[componentId].subcategories.push({
                    name: subcategoryName,
                    description: subcategoryDescription,
                    score: 0
                });
            }
        });

        renderGradeTable();
        $('#subcategoryModal').modal('hide');
    }

    function onSaveGrades(e) {
        e.preventDefault();
        const sectionId = $('#section').val();
        const subjectId = $('#subject').val();
        const grades = collectGradesData();

        $.ajax({
            url: '/AcadMeter/server/controllers/grade_management_controller.php',
            method: 'POST',
            data: {
                action: 'save_grades',
                section_id: sectionId,
                subject_id: subjectId,
                quarter: currentQuarter,
                academic_year: academicYear,
                grades: JSON.stringify(grades)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showSuccess('Grades saved successfully');
                    fetchStudentsAndGrades(); // Refresh the data
                } else {
                    showError('Error saving grades: ' + response.message);
                }
            },
            error: handleAjaxError
        });
    }

    // Helper functions
    function renderGradeTable() {
        const tableBody = currentTab === 'summary' ? $('#gradeTableBody') : $('#detailedGradeTableBody');
        tableBody.empty();

        if (Object.keys(studentsData).length === 0) {
            tableBody.append('<tr><td colspan="' + (components.length + 4) + '">No students found for this section and subject.</td></tr>');
            return;
        }

        $.each(studentsData, function(studentId, studentData) {
            const row = $('<tr></tr>');
            row.append($('<td></td>').text(studentData.student_name).addClass('student-name').attr('data-student-id', studentId));

            $.each(components, function(index, component) {
                const componentGrade = studentData.components[component.component_id] || { grade: '', subcategories: [] };
                const cell = $('<td></td>').addClass('grade-cell');

                if (currentTab === 'summary') {
                    cell.append($('<input>')
                        .addClass('form-control grade-input')
                        .attr({
                            type: 'number',
                            min: '0',
                            max: '100',
                            step: '0.01',
                            'data-student-id': studentId,
                            'data-component-id': component.component_id
                        })
                        .val(componentGrade.grade)
                        .on('input', updateGrades));
                } else {
                    const componentTotal = $('<div></div>')
                        .addClass('component-total')
                        .text(`${component.name} Total: ${componentGrade.grade || '0'}`);
                    cell.append(componentTotal);

                    const subcategoriesContainer = $('<div></div>').addClass('subcategories');
                    $.each(componentGrade.subcategories, function(index, subcategory) {
                        subcategoriesContainer.append(createSubcategoryRow(studentId, component.component_id, subcategory));
                    });
                    cell.append(subcategoriesContainer);
                }

                row.append(cell);
            });

            // Add Initial Grade, Quarterly Grade, and Remarks columns
            const initialGrade = calculateInitialGrade(studentData.components);
            const quarterlyGrade = calculateQuarterlyGrade(studentData.components);
            const remarks = getRemarks(quarterlyGrade);

            row.append($('<td></td>').addClass('initial-grade').text(initialGrade.toFixed(2)));
            row.append($('<td></td>').addClass('quarterly-grade').text(quarterlyGrade));
            row.append($('<td></td>').addClass('remarks').text(remarks).addClass(quarterlyGrade >= 75 ? 'text-success' : 'text-danger'));

            tableBody.append(row);
        });
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
        return row;
    }

    function updateGrades() {
        const studentId = $(this).data('student-id');
        const componentId = $(this).data('component-id');
        const grade = parseFloat($(this).val()) || 0;

        if (!studentsData[studentId]) {
            studentsData[studentId] = { components: {} };
        }
        if (!studentsData[studentId].components) {
            studentsData[studentId].components = {};
        }
        if (!studentsData[studentId].components[componentId]) {
            studentsData[studentId].components[componentId] = { grade: 0, subcategories: [] };
        }
        studentsData[studentId].components[componentId].grade = grade;

        updateStudentTotals(studentId);
    }

    function updateSubcategoryScore() {
        const studentId = $(this).data('student-id');
        const componentId = $(this).data('component-id');
        const subcategoryName = $(this).data('subcategory');
        const score = parseFloat($(this).val()) || 0;

        if (!studentsData[studentId].components[componentId]) {
            studentsData[studentId].components[componentId] = { grade: 0, subcategories: [] };
        }

        const subcategory = studentsData[studentId].components[componentId].subcategories.find(s => s.name === subcategoryName);
        if (subcategory) {
            subcategory.score = score;
        } else {
            studentsData[studentId].components[componentId].subcategories.push({ name: subcategoryName, score: score, description: '' });
        }

        updateComponentTotal(studentId, componentId);
        updateStudentTotals(studentId);
    }

    function updateComponentTotal(studentId, componentId) {
        const componentData = studentsData[studentId].components[componentId];
        if (componentData && componentData.subcategories.length > 0) {
            const total = componentData.subcategories.reduce((sum, sub) => sum + (parseFloat(sub.score) || 0), 0) / componentData.subcategories.length;
            componentData.grade = Math.round(total * 100) / 100; // Round to 2 decimal places

            // Update the detailed tab
            $(`#detailedGradeTableBody tr`).each(function() {
                const currentRow = $(this);
                const rowStudentId = currentRow.find('.student-name').data('student-id');
                if (rowStudentId === parseInt(studentId)) {
                    currentRow.find('.grade-cell').each(function() {
                        const headerText = $(this).find('.component-header').text();
                        if (headerText.includes(componentIdToDetails[componentId].name)) {
                            $(this).find('.component-total').text(`${componentIdToDetails[componentId].name} Total: ${componentData.grade}`);
                        }
                    });
                }
            });

            // Update the summary tab
            $(`#gradeTableBody tr`).each(function() {
                const currentRow = $(this);
                const rowStudentId = currentRow.find('.student-name').data('student-id');
                if (rowStudentId === parseInt(studentId)) {
                    currentRow.find('.grade-cell input[data-component-id="' + componentId + '"]').val(componentData.grade);
                }
            });

            // Update student totals
            updateStudentTotals(studentId);
        }
    }

    function updateStudentTotals(studentId) {
        const studentComponents = studentsData[studentId].components;
        const initialGrade = calculateInitialGrade(studentComponents);
        const quarterlyGrade = calculateQuarterlyGrade(studentComponents);
        const remarks = getRemarks(quarterlyGrade);

        // Update summary tab
        $(`#gradeTableBody tr`).each(function() {
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

        // Update detailed tab
        $(`#detailedGradeTableBody tr`).each(function() {
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
        // Currently mirrors initial grade; can be customized for different computations
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
});
