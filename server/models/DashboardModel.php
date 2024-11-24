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
        $query = "SELECT COUNT(DISTINCT s.student_id) as total
                  FROM students s
                  JOIN grades g ON s.student_id = g.student_id
                  GROUP BY s.student_id
                  HAVING AVG(g.grade) <= 74";
        $result = $this->conn->query($query);
        if ($result) {
            return $result->num_rows;
        }
        return 0;
    }

    public function getAveragePerformanceByMonth($year = null)
    {
        if ($year === null) {
            $year = date('Y');
        }

        $query = "SELECT 
                    MONTH(g.created_at) as month,
                    AVG(g.grade) as average_grade
                  FROM 
                    grades g
                  WHERE 
                    YEAR(g.created_at) = ?
                  GROUP BY 
                    MONTH(g.created_at)
                  ORDER BY 
                    MONTH(g.created_at)";

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