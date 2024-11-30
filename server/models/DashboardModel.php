<?php
// File: server/models/DashboardModel.php

class DashboardModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTotalStudents() {
        $query = "SELECT COUNT(*) as total FROM students";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        return 0;
    }

    public function getAtRiskStudentsCount() {
        $query = "SELECT COUNT(DISTINCT temp.student_id) as total
                  FROM (
                      SELECT g.student_id,
                             g.section_id,
                             g.subject_id,
                             g.quarter,
                             SUM(g.grade * gc.weight) / SUM(gc.weight) as final_grade
                      FROM grades g
                      JOIN grade_components gc ON g.component_id = gc.component_id
                      GROUP BY g.student_id, g.section_id, g.subject_id, g.quarter
                      HAVING final_grade < 75.00
                  ) temp";
        
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        return 0;
    }

    public function getAveragePerformanceByMonth($year = null)
    {
        if ($year === null) {
            $year = date('Y');
        }

        $query = "WITH MonthlyStudentGrades AS (
                    SELECT 
                        MONTH(g.created_at) as month,
                        g.student_id,
                        gc.weight,
                        LEAST(g.grade, 100) as grade  -- Ensure no grade exceeds 100
                    FROM 
                        grades g
                        JOIN grade_components gc ON g.component_id = gc.component_id
                    WHERE 
                        YEAR(g.created_at) = ?
                ),
                FinalGrades AS (
                    SELECT 
                        month,
                        student_id,
                        LEAST(SUM(weight * grade) / 100, 100) as final_grade  -- Ensure final grade doesn't exceed 100
                    FROM 
                        MonthlyStudentGrades
                    GROUP BY 
                        month, student_id
                )
                SELECT 
                    month,
                    AVG(final_grade) as average_grade
                FROM 
                    FinalGrades
                GROUP BY 
                    month
                ORDER BY 
                    month";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $result = $stmt->get_result();

        $performanceData = array_fill(1, 12, ['month' => '', 'average_grade' => null]);

        while ($row = $result->fetch_assoc()) {
            $monthNumber = $row['month'];
            $monthName = date('M', mktime(0, 0, 0, $monthNumber, 1));
            $performanceData[$monthNumber] = [
                'month' => $monthName,
                'average_grade' => round($row['average_grade'], 2)
            ];
        }

        $stmt->close();
        return array_values($performanceData);
    }
}