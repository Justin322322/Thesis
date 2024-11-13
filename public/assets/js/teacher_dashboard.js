$(document).ready(function () {
    // Highlight active link based on current view
    const currentView = new URLSearchParams(window.location.search).get('view') || 'dashboard_overview';
    $('.sidebar-link').removeClass('active');
    $(`.sidebar-link[href*="view=${currentView}"]`).addClass('active');

    // Toggle Notification Dropdown
    $('#notificationIcon').click(function (e) {
        e.preventDefault();
        $('#notification-dropdown').toggle();
    });

    // Hide notification dropdown when clicking outside
    $(document).click(function (event) {
        const $target = $(event.target);
        if (!$target.closest('#notificationIcon').length &&
            !$target.closest('#notification-dropdown').length) {
            $('#notification-dropdown').hide();
        }
    });

    // Add rows for quizzes, assignments, extracurricular activities
    $(document).on('click', '.add-quiz-row, .add-assignment-row, .add-extracurricular-row', function (e) {
        e.preventDefault();
        const quarter = $(this).data('quarter');
        const type = $(this).hasClass('add-quiz-row') ? 'Quiz' : $(this).hasClass('add-assignment-row') ? 'Assignment' : 'Extracurricular';
        let newRow;

        if (type === 'Quiz') {
            newRow = `
                <tr>
                    <td><input type="text" class="form-control" placeholder="Quiz Name"></td>
                    <td><input type="number" class="form-control score" placeholder="Score"></td>
                    <td><input type="number" class="form-control items" placeholder="Total Items"></td>
                    <td><input type="number" class="form-control weight" placeholder="Weight"></td>
                    <td><span class="weighted-grade">0%</span></td>
                    <td><button class="btn btn-danger btn-sm remove-row">&times;</button></td>
                </tr>`;
        } else if (type === 'Assignment') {
            newRow = `
                <tr>
                    <td><input type="text" class="form-control" placeholder="Assignment Name"></td>
                    <td><input type="number" class="form-control score" placeholder="Score"></td>
                    <td><input type="number" class="form-control items" placeholder="Total Items"></td>
                    <td><input type="number" class="form-control weight" placeholder="Weight"></td>
                    <td><span class="weighted-grade">0%</span></td>
                    <td><button class="btn btn-danger btn-sm remove-row">&times;</button></td>
                </tr>`;
        } else if (type === 'Extracurricular') {
            newRow = `
                <tr>
                    <td><input type="text" class="form-control" placeholder="Activity Name"></td>
                    <td><input type="number" class="form-control score" placeholder="Score"></td>
                    <td><input type="number" class="form-control items" placeholder="Total Items"></td>
                    <td><span class="weighted-grade">0%</span></td>
                    <td><button class="btn btn-danger btn-sm remove-row">&times;</button></td>
                </tr>`;
        }

        $(`#quarter${quarter}${type}Grades`).append(newRow);
    });

    // Remove row functionality
    $(document).on('click', '.remove-row', function (e) {
        e.preventDefault();
        $(this).closest('tr').remove();
    });

    // Compute Grade functionality
    $('.compute-grade').click(function (e) {
        e.preventDefault();
        const quarter = $(this).data('quarter');
        let totalWeightedScore = 0;
        let totalWeight = 0;

        // Function to calculate weighted grades
        function calculateWeightedGrades(selector, hasWeight = true) {
            $(selector).find('tr').each(function () {
                const score = parseFloat($(this).find('.score').val()) || 0;
                const items = parseFloat($(this).find('.items').val()) || 1;
                let weight = hasWeight ? parseFloat($(this).find('.weight').val()) || 0 : 0;
                const weightedGrade = ((score / items) * weight).toFixed(2);
                $(this).find('.weighted-grade').text(`${weightedGrade}%`);
                totalWeightedScore += parseFloat(weightedGrade);
                totalWeight += weight;
            });
        }

        // Calculate grades for Quizzes, Assignments, and Extracurriculars
        calculateWeightedGrades(`#quarter${quarter}QuizGrades`, true);
        calculateWeightedGrades(`#quarter${quarter}AssignmentGrades`, true);
        calculateWeightedGrades(`#quarter${quarter}ExtracurricularGrades`, false);

        // Midterm Exam calculations
        const midtermScore = parseFloat($('.score-midterm').val()) || 0;
        const midtermItems = parseFloat($('.items-midterm').val()) || 1;
        const midtermWeight = 30;
        const midtermWeightedGrade = ((midtermScore / midtermItems) * midtermWeight).toFixed(2);
        $('.weighted-grade-midterm').text(`${midtermWeightedGrade}%`);
        totalWeightedScore += parseFloat(midtermWeightedGrade);
        totalWeight += midtermWeight;

        // Final Exam calculations
        const finalScore = parseFloat($('.score-final').val()) || 0;
        const finalItems = parseFloat($('.items-final').val()) || 1;
        const finalWeight = 40;
        const finalWeightedGrade = ((finalScore / finalItems) * finalWeight).toFixed(2);
        $('.weighted-grade-final').text(`${finalWeightedGrade}%`);
        totalWeightedScore += parseFloat(finalWeightedGrade);
        totalWeight += finalWeight;

        // Calculate and display the final grade
        const finalGrade = totalWeight > 0 ? (totalWeightedScore / totalWeight).toFixed(2) : 0;
        alert(`Quarter ${quarter} Final Grade: ${finalGrade}%`);
    });

    // Fetch At-Risk Students Data for Predictive Analytics
    fetch('/api/at_risk_students')
        .then(response => response.json())
        .then(data => {
            const tbody = $("#predictive-analytics tbody");
            tbody.empty();  // Clear existing data

            data.forEach(student => {
                const row = `
                    <tr>
                        <td>${student.name}</td>
                        <td>${student.current_grade}%</td>
                        <td>${student.risk_probability > 0.8 ? 'High' : 'Moderate'} (${Math.round(student.risk_probability * 100)}%)</td>
                        <td>${student.suggested_intervention}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        })
        .catch(error => console.error('Error fetching at-risk students:', error));
});
