document.addEventListener('DOMContentLoaded', () => {
    const state = {
        currentQuarter: 1,
        currentTab: 'summary',
        students: [],
        grades: {},
        selectedComponent: '',
        isSaving: false
    };

    // Ensure subcategories is defined globally
    if (typeof window.subcategories === 'undefined') {
        console.error('Subcategories are not defined. Please check if they are properly passed from PHP.');
        window.subcategories = {}; // Initialize with an empty object to prevent errors
    }

    // Event listeners
    document.querySelectorAll('.quarter-tabs .tab-btn').forEach(btn => 
        btn.addEventListener('click', handleQuarterChange));

    document.querySelectorAll('.grade-tabs .grade-tab').forEach(tab => 
        tab.addEventListener('click', handleTabChange));

    document.getElementById('section').addEventListener('change', handleSectionChange);
    document.getElementById('subject').addEventListener('change', handleSubjectChange);
    document.getElementById('gradeForm').addEventListener('submit', handleFormSubmit);

    // Use event delegation for dynamically added elements
    document.addEventListener('click', handleDynamicClicks);

    // Use event delegation for grade inputs
    document.addEventListener('input', throttle(handleGradeInput, 200));
    document.addEventListener('blur', formatGradeInput, true);
    document.addEventListener('keydown', preventSubmit);
    document.addEventListener('focus', handleGradeFocus, true);

    document.getElementById('saveSubcategory').addEventListener('click', handleSaveSubcategory);
    document.getElementById('subcategorySelect').addEventListener('change', handleSubcategorySelectChange);

    ['section', 'subject', 'academic_year'].forEach(id => {
        const element = document.getElementById(id);
        element.addEventListener('change', updateSaveButtonState);
        element.addEventListener('input', updateSaveButtonState);
    });

    // Initialize tooltips
    initializeTooltips();

    // Initial data load
    fetchSections();

    function handleQuarterChange(event) {
        state.currentQuarter = parseInt(event.target.dataset.quarter);
        updateActiveTab('.quarter-tabs .tab-btn', event.target);
        fetchGrades();
    }

    function handleTabChange(event) {
        state.currentTab = event.target.dataset.tab;
        updateActiveTab('.grade-tabs .grade-tab', event.target);
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.id === `${state.currentTab}-tab`);
        });
        renderGradeTable();
    }

    function updateActiveTab(selector, activeElement) {
        document.querySelectorAll(selector).forEach(el => el.classList.remove('active'));
        activeElement.classList.add('active');
    }

    function handleSectionChange() {
        const sectionId = document.getElementById('section').value;
        if (sectionId) {
            fetchSubjects(sectionId);
            document.getElementById('subject').disabled = false;
        } else {
            document.getElementById('subject').disabled = true;
            document.getElementById('subject').innerHTML = '<option value="">-- Select Subject --</option>';
        }
    }

    function handleSubjectChange() {
        fetchStudents();
        fetchGrades();
    }

    function handleDynamicClicks(event) {
        if (event.target.classList.contains('add-subcategory-btn')) {
            handleAddSubcategory(event);
        } else if (event.target.classList.contains('remove-subcategory-btn')) {
            handleRemoveSubcategory(event);
        }
    }

    function handleAddSubcategory(event) {
        event.preventDefault();
        state.selectedComponent = event.target.dataset.component;
        console.log("Selected Component:", state.selectedComponent);
        openSubcategoryModal();
    }

    function handleRemoveSubcategory(event) {
        event.preventDefault();
        const button = event.target.closest('.remove-subcategory-btn');
        const { studentId, component, subcategoryIndex } = button.dataset;

        if (confirm('Are you sure you want to remove this subcategory?')) {
            if (state.grades[studentId]?.[component]?.subcategories) {
                state.grades[studentId][component].subcategories.splice(parseInt(subcategoryIndex), 1);
                
                // Recalculate the main component grade
                state.grades[studentId][component].grade = calculateAverageSubcategoryGrade(state.grades[studentId][component].subcategories);
            }

            renderGradeTable();
        }
    }

    async function fetchSections() {
        try {
            const response = await fetch('/AcadMeter/server/controllers/grade_management_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=fetch_sections&instructor_id=${instructorId}`
            });
            const data = await response.json();
            if (data.status === 'success') {
                populateSelect('section', data.sections, 'section_id', 'section_name');
            } else {
                console.error('Failed to fetch sections:', data.message);
            }
        } catch (error) {
            handleAjaxError(error);
        }
    }

    async function fetchSubjects(sectionId) {
        try {
            const response = await fetch('/AcadMeter/server/controllers/grade_management_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=fetch_subjects&section_id=${sectionId}`
            });
            const data = await response.json();
            if (data.status === 'success') {
                populateSelect('subject', data.subjects, 'subject_id', 'subject_name');
            } else {
                console.error('Failed to fetch subjects:', data.message);
            }
        } catch (error) {
            handleAjaxError(error);
        }
    }

    async function fetchStudents() {
        const sectionId = document.getElementById('section').value;
        try {
            const response = await fetch('/AcadMeter/server/controllers/grade_management_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=fetch_students&section_id=${sectionId}`
            });
            const data = await response.json();
            if (data.status === 'success') {
                state.students = data.students;
                renderGradeTable();
            } else {
                console.error('Failed to fetch students:', data.message);
            }
        } catch (error) {
            handleAjaxError(error);
        }
    }

    async function fetchGrades() {
        const sectionId = document.getElementById('section').value;
        const subjectId = document.getElementById('subject').value;
        const academicYear = document.getElementById('academic_year').value;
        if (!sectionId || !subjectId || !academicYear) return;

        try {
            const response = await fetch('/AcadMeter/server/controllers/grade_management_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=fetch_grades&section_id=${sectionId}&subject_id=${subjectId}&quarter=${state.currentQuarter}&academic_year=${academicYear}`
            });
            const data = await response.json();
            if (data.status === 'success') {
                state.grades = data.grades;
                renderGradeTable();
            } else {
                console.error('Failed to fetch grades:', data.message);
            }
        } catch (error) {
            handleAjaxError(error);
        }
    }

    function renderGradeTable() {
        const tableBody = document.getElementById(`${state.currentTab === 'summary' ? 'gradeTableBody' : 'detailedGradeTableBody'}`);
        tableBody.innerHTML = '';

        state.students.forEach(student => {
            const studentGrades = state.grades[student.student_id] || {};
            const initialGrade = calculateInitialGrade(studentGrades);
            const quarterlyGrade = calculateQuarterlyGrade(initialGrade);
            const remarks = getRemarks(quarterlyGrade);

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="student-name">${student.student_name}</td>
                ${components.map(component => 
                    state.currentTab === 'summary' 
                        ? renderSummaryCell(student, component, studentGrades)
                        : renderDetailedCell(student, component, studentGrades)
                ).join('')}
                <td class="initial-grade">${initialGrade !== null ? initialGrade.toFixed(2) : ''}</td>
                <td class="quarterly-grade ${quarterlyGrade < 75 ? 'failed-grade' : ''}">${quarterlyGrade !== null ? quarterlyGrade : ''}</td>
                <td class="remarks ${remarks === 'Failed' ? 'failed-grade' : 'passed-grade'}">${remarks}</td>
            `;

            tableBody.appendChild(row);
        });

        initializeTooltips();
        setGradeInputAttributes();
    }

    function renderSummaryCell(student, component, studentGrades) {
        const componentId = components.findIndex(c => c.key === component.key) + 1;
        const grade = studentGrades[componentId]?.grade || '';
        return `
            <td>
                <input type="text" class="form-control grade-input" 
                    data-student-id="${student.student_id}" 
                    data-component="${componentId}" 
                    value="${grade}" 
                    style="width: 80px; text-align: right; padding-right: 5px;"
                    title="Total score for all ${component.name.toLowerCase()}">
            </td>
        `;
    }

    function renderDetailedCell(student, component, studentGrades) {
        const componentId = components.findIndex(c => c.key === component.key) + 1;
        const componentGrade = studentGrades[componentId];
        let subcategoriesHtml = '';

        if (componentGrade?.subcategories) {
            subcategoriesHtml = componentGrade.subcategories.map((subcat, index) => `
                <div class="subcategory-row">
                    <span class="subcategory-name" title="${subcat.description || ''}">${subcat.name || ''}</span>
                    <input type="text" class="form-control subcategory-score" 
                        data-student-id="${student.student_id}" 
                        data-component="${componentId}"
                        data-subcategory-index="${index}"
                        value="${subcat.grade !== null && subcat.grade !== undefined ? subcat.grade : ''}" 
                        style="width: 80px; text-align: right; padding-right: 5px;"
                        title="Score for ${subcat.name || ''}">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-subcategory-btn"
                        data-student-id="${student.student_id}"
                        data-component="${componentId}"
                        data-subcategory-index="${index}">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            `).join('');
        }

        return `
            <td class="grade-cell">
                <div class="component-total" title="Average of all ${component.name.toLowerCase()} activities">
                    ${componentGrade && componentGrade.grade !== null ? componentGrade.grade.toFixed(2) : ''}
                </div>
                <div class="subcategories">
                    ${subcategoriesHtml}
                </div>
            </td>
        `;
    }

    function handleGradeInput(event) {
        const input = event.target;
        if (!input.classList.contains('grade-input') && !input.classList.contains('subcategory-score')) {
            return;
        }

        let value = input.value;
        const cursorPosition = input.selectionStart;

        // Remove any non-digit characters except the first decimal point
        value = value.replace(/[^\d.]/g, '').replace(/\./, 'x').replace(/\./g, '').replace(/x/, '.');

        // Limit to 3 digits before decimal and 2 after
        const [integerPart, fractionalPart] = value.split('.');
        value = `${integerPart.slice(0, 3)}${fractionalPart ? `.${fractionalPart.slice(0, 2)}` : ''}`;

        // Prevent value from exceeding 100
        value = Math.min(parseFloat(value) || 0, 100).toString();

        // Only update the value if it has changed
        if (input.value !== value) {
            input.value = value;
            
            // Only try to set the selection range if the input is not of type "number"
            if (input.type !== 'number') {
                // Restore cursor position
                const newCursorPosition = Math.min(cursorPosition, value.length);
                input.setSelectionRange(newCursorPosition, newCursorPosition);
            }
        }

        updateGrade(event);
    }

    function formatGradeInput(event) {
        const input = event.target;
        if (!input.classList.contains('grade-input') && !input.classList.contains('subcategory-score')) {
            return;
        }

        let value = input.value.trim();
        if (value === '') return;

        let numericValue = parseFloat(value);
        if (isNaN(numericValue)) {
            numericValue = null;
        } else if (numericValue > 100) {
            numericValue = 100;
        }

        input.value = numericValue !== null ? numericValue.toFixed(2) : '';
    }

    function handleGradeFocus(event) {
        if (event.target.classList.contains('grade-input') || event.target.classList.contains('subcategory-score')) {
            event.target.select();
        }
    }

    function preventSubmit(event) {
        if ((event.target.classList.contains('grade-input') || event.target.classList.contains('subcategory-score')) && event.key === 'Enter') {
            event.preventDefault();
            event.target.blur();
        }
    }

    function updateGrade(event) {
        const input = event.target;
        const { studentId, component, subcategoryIndex } = input.dataset;
        
        const numericValue = parseFloat(input.value);
        if (!state.grades[studentId]) {
            state.grades[studentId] = {};
        }
        if (!state.grades[studentId][component]) {
            state.grades[studentId][component] = { grade: null, subcategories: [] };
        }

        if (input.classList.contains('subcategory-score')) {
            if (!state.grades[studentId][component].subcategories[subcategoryIndex]) {
                state.grades[studentId][component].subcategories[subcategoryIndex] = {};
            }
            state.grades[studentId][component].subcategories[subcategoryIndex].grade = isNaN(numericValue) ? null : numericValue;
            // Recalculate the main component grade
            state.grades[studentId][component].grade = calculateAverageSubcategoryGrade(state.grades[studentId][component].subcategories);
        } else {
            state.grades[studentId][component].grade = isNaN(numericValue) ? null : numericValue;
        }

        updateGradeDisplay(input, studentId, component);
    }

    function updateGradeDisplay(input, studentId, component) {
        const row = input.closest('tr');
        const componentTotal = input.closest('td').querySelector('.component-total');
        if (componentTotal) {
            componentTotal.textContent = state.grades[studentId][component].grade !== null 
                ? state.grades[studentId][component].grade.toFixed(2) 
                : '';
        }

        const initialGrade = calculateInitialGrade(state.grades[studentId]);
        const quarterlyGrade = calculateQuarterlyGrade(initialGrade);
        const remarks = getRemarks(quarterlyGrade);

        row.querySelector('.initial-grade').textContent = initialGrade !== null ? initialGrade.toFixed(2) : '';
        row.querySelector('.quarterly-grade').textContent = quarterlyGrade !== null ? quarterlyGrade : '';
        row.querySelector('.remarks').textContent = remarks;
        row.querySelector('.quarterly-grade').className = `quarterly-grade ${quarterlyGrade !== null && quarterlyGrade < 75 ? 'failed-grade' : ''}`;
        row.querySelector('.remarks').className = `remarks ${remarks === 'Failed' ? 'failed-grade' : 'passed-grade'}`;
    }

    function calculateInitialGrade(studentGrades) {
        let totalWeightedGrade = 0;
        let totalWeight = 0;

        components.forEach((component, index) => {
            const componentId = index + 1;
            const grade = studentGrades[componentId]?.grade;
            if (grade !== null && grade !== undefined) {
                totalWeightedGrade += grade * (component.weight / 100);
                totalWeight += component.weight / 100;
            }
        });

        return totalWeight > 0 ? totalWeightedGrade / totalWeight : null;
    }

    function calculateQuarterlyGrade(initialGrade) {
        return initialGrade !== null ? Math.round(initialGrade) : null;
    }

    function getRemarks(quarterlyGrade) {
        return quarterlyGrade !== null ? (quarterlyGrade >= 75 ? 'Passed' : 'Failed') : '';
    }

    function calculateAverageSubcategoryGrade(subcategories) {
        if (!subcategories || subcategories.length === 0) return null;
        const validSubcategories = subcategories.filter(subcat => subcat && subcat.grade != null);
        if (validSubcategories.length === 0) return null;
        const totalGrade = validSubcategories.reduce((sum, subcat) => sum + subcat.grade, 0);
        return totalGrade / validSubcategories.length;
    }

    function openSubcategoryModal() {
        if (state.selectedComponent) {
            document.getElementById('componentSelect').value = state.selectedComponent;
            document.getElementById('componentSelect').disabled = true;
            populateSubcategorySelect();
            $('#subcategoryModal').modal('show');
        } else {
            console.error('No component selected');
            alert('Please select a component before adding a subcategory.');
        }
    }

    async function populateSubcategorySelect() {
        const subcategorySelect = document.getElementById('subcategorySelect');
        subcategorySelect.innerHTML = '';
        console.log("Populating subcategory select for component:", state.selectedComponent);
        console.log("Available subcategories:", window.subcategories);

        if (!window.subcategories[state.selectedComponent] || window.subcategories[state.selectedComponent].length === 0) {
            try {
                const response = await fetch('/AcadMeter/server/controllers/grade_management_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=fetch_subcategories&component_key=${state.selectedComponent}`
                });
                const data = await response.json();
                if (data.status === 'success') {
                    window.subcategories[state.selectedComponent] = data.subcategories;
                    updateSubcategoryDropdown();
                } else {
                    console.error('Failed to fetch subcategories:', data.message);
                }
            } catch (error) {
                console.error('Error fetching subcategories:', error);
            }
        } else {
            updateSubcategoryDropdown();
        }
    }

    function updateSubcategoryDropdown() {
        const subcategorySelect = document.getElementById('subcategorySelect');
        subcategorySelect.innerHTML = window.subcategories[state.selectedComponent]
            .map(subcat => `<option value="${subcat.name}">${subcat.name}</option>`)
            .join('');
        subcategorySelect.dispatchEvent(new Event('change'));
    }

    function handleSaveSubcategory() {
        const subcategoryName = document.getElementById('subcategorySelect').value;
        const subcategoryDescription = document.getElementById('subcategoryDescription').value;

        if (!subcategoryName) {
            alert('Please select a subcategory.');
            return;
        }

        console.log("Saving subcategory:", subcategoryName);

        state.students.forEach(student => {
            const componentId = components.findIndex(c => c.key === state.selectedComponent) + 1;
            state.grades[student.student_id] = state.grades[student.student_id] || {};
            state.grades[student.student_id][componentId] = state.grades[student.student_id][componentId] || { subcategories: [] };

            if (!state.grades[student.student_id][componentId].subcategories.some(subcat => subcat.name === subcategoryName)) {
                state.grades[student.student_id][componentId].subcategories.push({
                    name: subcategoryName,
                    description: subcategoryDescription,
                    grade: null
                });
                console.log("Added subcategory for student:", student.student_id);
            } else {
                console.log("Subcategory already exists for student:", student.student_id);
            }
        });

        $('#subcategoryModal').modal('hide');
        renderGradeTable();
    }

    function handleSubcategorySelectChange() {
        const selectedSubcategory = document.getElementById('subcategorySelect').value;
        console.log("Selected Subcategory:", selectedSubcategory);
        console.log("Current subcategories:", window.subcategories);
        console.log("Selected Component:", state.selectedComponent);

        if (window.subcategories && state.selectedComponent && window.subcategories[state.selectedComponent]) {
            const subcategoryInfo = window.subcategories[state.selectedComponent].find(subcat => subcat.name === selectedSubcategory);
            if (subcategoryInfo) {
                document.getElementById('subcategoryDescription').value = subcategoryInfo.description;
            } else {
                document.getElementById('subcategoryDescription').value = '';
                console.warn('No matching subcategory found for:', selectedSubcategory);
            }
        } else {
            document.getElementById('subcategoryDescription').value = '';
            console.warn('Subcategories or selected component is undefined:', { subcategories: window.subcategories, selectedComponent: state.selectedComponent });
        }
    }

    function initializeTooltips() {
        $('[data-toggle="tooltip"]').tooltip({
            trigger: 'click',
            placement: 'top'
        }).on('click', function (e) {
            e.preventDefault();
            $(this).tooltip('toggle');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.hasAttribute('data-toggle') || e.target.getAttribute('data-toggle') !== 'tooltip') {
                $('[data-toggle="tooltip"]').tooltip('hide');
            }
        });
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        if (validateForm()) {
            confirmSaveGrades();
        }
    }

    function validateForm() {
        const sectionId = document.getElementById('section').value;
        const subjectId = document.getElementById('subject').value;
        const academicYear = document.getElementById('academic_year').value;

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
        const sectionId = document.getElementById('section').value;
        const subjectId = document.getElementById('subject').value;
        const academicYear = document.getElementById('academic_year').value;
        document.querySelector('.save-grades').disabled = !(sectionId && subjectId && academicYear);
    }

    function handleAjaxError(error) {
        console.error('AJAX Error:', error);

        if (error.responseText) {
            console.error('Server Response:', error.responseText);
        }

        alert('An error occurred. Please check the console for details.');
        document.querySelector('.save-grades').disabled = false;
        document.querySelector('.save-grades').innerHTML = '<i class="fas fa-save"></i> Save Grades';
        state.isSaving = false;
    }

    function setGradeInputAttributes() {
        document.querySelectorAll('.grade-input, .subcategory-score').forEach(input => {
            input.setAttribute('type', 'text'); // Change type to 'text' instead of 'number'
            input.setAttribute('inputmode', 'decimal');
            input.setAttribute('pattern', '[0-9]*\\.?[0-9]*');
            input.setAttribute('maxlength', '6');
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('spellcheck', 'false');
        });
    }

    function confirmSaveGrades() {
        if (confirm('Are you sure you want to save the grades?')) {
            saveGrades();
        }
    }

    async function saveGrades() {
        if (state.isSaving) return;

        state.isSaving = true;
        const sectionId = document.getElementById('section').value;
        const subjectId = document.getElementById('subject').value;
        const academicYear = document.getElementById('academic_year').value;

        const saveButton = document.querySelector('.save-grades');
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const cleanGrades = Object.fromEntries(
            Object.entries(state.grades).map(([studentId, components]) => [
                studentId,
                Object.fromEntries(
                    Object.entries(components).map(([componentId, data]) => [
                        componentId,
                        {
                            grade: data.grade,
                            subcategories: data.subcategories.map(subcat => ({
                                name: subcat.name ? String(subcat.name).replace(/[<>]/g, '') : '',
                                description: subcat.description ? String(subcat.description).replace(/[<>]/g, '') : '',
                                grade: subcat.grade
                            }))
                        }
                    ])
                )
            ])
        );

        const gradesData = encodeURIComponent(JSON.stringify(cleanGrades));
        console.log('Clean grades data being sent:', cleanGrades);

        try {
            const response = await fetch('/AcadMeter/server/controllers/grade_management_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=save_grades&section_id=${sectionId}&subject_id=${subjectId}&quarter=${state.currentQuarter}&academic_year=${academicYear}&grades=${gradesData}`
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            handleSaveGradesSuccess(result);
        } catch (error) {
            handleAjaxError(error);
        } finally {
            state.isSaving = false;
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fas fa-save"></i> Save Grades';
        }
    }

    function handleSaveGradesSuccess(response) {
        if (response.status === 'success') {
            alert('Grades saved successfully!');
            fetchGrades();
        } else {
            console.error('Failed to save grades:', response.message);
            alert('Failed to save grades. Please try again.');
        }
    }

    function throttle(func, limit) {
        let lastFunc;
        let lastRan;
        return function (...args) {
            if (!lastRan) {
                func.apply(this, args);
                lastRan = Date.now();
            } else {
                clearTimeout(lastFunc);
                lastFunc = setTimeout(() => {
                    if (Date.now() - lastRan >= limit) {
                        func.apply(this, args);
                        lastRan = Date.now();
                    }
                }, limit - (Date.now() - lastRan));
            }
        };
    }

    function populateSelect(elementId, options, valueKey, textKey) {
        const select = document.getElementById(elementId);
        select.innerHTML = `<option value="">-- Select ${elementId.charAt(0).toUpperCase() + elementId.slice(1)} --</option>`;
        options.forEach(option => {
            select.innerHTML += `<option value="${option[valueKey]}">${option[textKey]}</option>`;
        });
    }
});