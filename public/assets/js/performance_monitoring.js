// performance_monitoring.js

document.addEventListener('DOMContentLoaded', async function() {
    // Initialize Bootstrap tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Set initial section selection
    const sectionSelect = document.getElementById('sectionSelect');
    const optionsCount = sectionSelect.options.length;

    if (optionsCount === 1) {
        // Only one section available; select it automatically
        sectionSelect.value = sectionSelect.options[0].value;
        
        // Optionally, disable the dropdown to prevent unnecessary selection
        sectionSelect.disabled = true;
        
        // Load data for the single section
        await Promise.all([
            loadClassPerformanceChart(),
            loadSectionSummaryChart(sectionSelect.value),
            loadClassStandings(sectionSelect.value),
            loadTopPerformersChart(sectionSelect.value)
        ]).catch(error => console.error('Error loading initial data:', error));
    } else {
        // Multiple sections available; include "All Sections"
        sectionSelect.value = "0";
        
        // Load initial data with "All Sections"
        await Promise.all([
            loadClassPerformanceChart(),
            loadSectionSummaryChart("0"),
            loadClassStandings("0"),
            loadTopPerformersChart("0")
        ]).catch(error => console.error('Error loading initial data:', error));
    }

    // Update charts when section is changed (only if multiple sections exist)
    if (optionsCount > 1) {
        sectionSelect.addEventListener('change', function() {
            const sectionId = this.value;
            Promise.all([
                loadSectionSummaryChart(sectionId),
                loadClassStandings(sectionId),
                loadTopPerformersChart(sectionId)
            ]).catch(error => console.error('Error updating charts:', error));
        });
    }

    // Remove all student select related code
});

let classPerformanceChart, sectionSummaryChart, studentSubjectChart;

function loadClassPerformanceChart() {
    fetch('/AcadMeter/server/controllers/get_class_performance.php')
        .then(response => response.json())
        .then(data => {
            if (classPerformanceChart) classPerformanceChart.destroy();
            
            if (data.length === 0) {
                document.getElementById('classPerformanceChart').getContext('2d').clearRect(0, 0, width, height);
                return;
            }

            const ctx = document.getElementById('classPerformanceChart').getContext('2d');
            classPerformanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.section_name),
                    datasets: [{
                        label: 'Class Average',
                        data: data.map(item => item.average_score),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Average Score (%)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Sections'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Average: ${context.parsed.y}%`;
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading class performance data:', error));
}

function loadSectionSummaryChart(sectionId = "0") {
    const sectionName = document.getElementById('sectionSelect').options[document.getElementById('sectionSelect').selectedIndex].text;
    const chartTitle = sectionId === "0" ? "Performance Breakdown" : `${sectionName} Performance Breakdown`;
    
    fetch('/AcadMeter/server/controllers/get_section_summary.php?section_id=' + sectionId)
        .then(response => response.json())
        .then(data => {
            const labels = data.map(item => `${item.grade_category} (Avg: ${item.average_grade}%)`);
            const percentages = data.map(item => item.percentage);
            const studentsData = data.map(item => item.students);

            const backgroundColors = {
                'Outstanding': 'green',
                'Very Satisfactory': '#20c997', // Changed from 'blue' to teal (#20c997)
                'Satisfactory': 'lightgray',
                'Fair': 'orange',
                'Needs Improvement': 'red'
            };

            if (sectionSummaryChart) sectionSummaryChart.destroy();

            sectionSummaryChart = new Chart(document.getElementById('sectionSummaryChart'), {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: percentages,
                        backgroundColor: labels.map(label => 
                            backgroundColors[label.split(' (')[0]]
                        )
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: chartTitle,
                            font: { size: 16 }
                        },
                        legend: { 
                            position: 'top',
                            labels: {
                                font: { size: 12 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const category = context.label.split(' (')[0];
                                    const students = studentsData[context.dataIndex];
                                    return [`${category}: ${context.parsed} student(s)`,
                                        ...students.map(s => `${s.name}: ${s.grade}%`)
                                    ];
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading section summary data:', error));
}

function loadClassStandings(sectionId = "0") {
    const tbody = document.querySelector('#classStandingsTable tbody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center">Loading class standings...</td></tr>';

    fetch('/AcadMeter/server/controllers/get_class_standings.php?section_id=' + sectionId)
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            if (contentType && contentType.indexOf('application/json') !== -1) {
                return response.json();
            } else {
                throw new TypeError('Unexpected response format');
            }
        })
        .then(data => {
            tbody.innerHTML = '';
            if (data.length > 0) {
                // Sort data by average_grade in descending order
                data.sort((a, b) => b.average_grade - a.average_grade);
                
                data.forEach((student, index) => {
                    const tr = document.createElement('tr');
                    const bgColor = getGradeCategoryColor(student.grade_category);
                    
                    tr.innerHTML = `
                        <td class="text-center">${index + 1}</td>
                        <td>${student.student_name}</td>
                        <td class="text-center" style="background-color: ${bgColor};">
                            ${student.average_grade}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center">No data available</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">Error loading data</td></tr>';
        });
}

// Replace existing loadStudentSubjectChart and related functions with this:
function loadTopPerformersChart(sectionId = "0") {
    fetch(`/AcadMeter/server/controllers/get_top_students_per_subject.php?section_id=${sectionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                if (studentSubjectChart) studentSubjectChart.destroy();
                document.getElementById('topPerformersList').innerHTML = 
                    '<div class="text-center">No data available</div>';
                return;
            }

            const subjects = [];
            const grades = [];
            const students = [];

            // Process data for chart
            data.forEach(item => {
                subjects.push(item.subject);
                grades.push(item.top_grade);
                students.push(item.student_name);
            });

            updateTopPerformersChart(subjects, grades, students);
            updateTopPerformersList(data);
        })
        .catch(error => console.error('Error loading top performers:', error));
}

function updateTopPerformersChart(subjects, grades, students) {
    if (studentSubjectChart) studentSubjectChart.destroy();

    studentSubjectChart = new Chart(document.getElementById('studentSubjectChart'), {
        type: 'bar',
        data: {
            labels: subjects,
            datasets: [{
                label: 'Top Grade',
                data: grades,
                backgroundColor: subjects.map(() => getRandomColor()),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Grade' }
                },
                x: {
                    title: { display: true, text: 'Subjects' }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const studentName = students[context.dataIndex];
                            return `${studentName}: ${context.raw}%`;
                        }
                    }
                }
            }
        }
    });
}

function updateTopPerformersList(data) {
    const container = document.getElementById('topPerformersList');
    container.innerHTML = '';

    if (data.length === 0) {
        container.innerHTML = '<div class="text-center">No data available</div>';
        return;
    }

    data.forEach(item => {
        const listItem = document.createElement('div');
        listItem.className = 'list-group-item';
        listItem.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${item.subject}</strong><br>
                    <small>${item.student_name}</small><br>
                    <small class="text-muted">Section: ${item.section_name}</small>
                </div>
                <span class="badge bg-success">${item.top_grade}%</span>
            </div>
        `;
        container.appendChild(listItem);
    });
}

// Add a new function to display the stack ranking
function displayStackRanking(rankingData) {
    const rankingContainer = document.getElementById('stackRankingContainer');
    rankingContainer.innerHTML = '';
    
    rankingData.forEach((student, index) => {
        const studentDiv = document.createElement('div');
        studentDiv.classList.add('ranking-item', 'mb-3');
        
        // Student name and average grade
        const header = document.createElement('strong');
        header.textContent = `${index + 1}. ${student.name} - Avg Grade: ${student.averageGrade.toFixed(2)}`;
        studentDiv.appendChild(header);
        
        // List of subjects and grades
        const subjectList = document.createElement('ul');
        student.subjects.forEach(subject => {
            const listItem = document.createElement('li');
            listItem.textContent = `${subject.subject}: ${subject.grade}`;
            subjectList.appendChild(listItem);
        });
        studentDiv.appendChild(subjectList);
        
        rankingContainer.appendChild(studentDiv);
    });
}

// Utility function to generate random colors for datasets
function getRandomColor() {
    const r = Math.floor(Math.random() * 200);
    const g = Math.floor(Math.random() * 200);
    const b = Math.floor(Math.random() * 200);
    return `rgba(${r}, ${g}, ${b}, 0.6)`;
}

// Add this helper function
function getGradeCategoryColor(category) {
    const colors = {
        'Outstanding': 'green',
        'Very Satisfactory': '#20c997',
        'Satisfactory': 'lightgray',
        'Fair': 'orange',
        'Needs Improvement': 'red'
    };
    return colors[category] || '#FFFFFF';
}

window.addEventListener('resize', function() {
    if (classPerformanceChart) classPerformanceChart.resize();
    if (sectionSummaryChart) classPerformanceChart.resize();
    if (studentSubjectChart) classPerformanceChart.resize();
});
