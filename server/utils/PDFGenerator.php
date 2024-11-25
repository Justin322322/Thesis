<?php
// server/utils/PDFGenerator.php

require_once __DIR__ . '/../../vendor/autoload.php';

class PDFGenerator {
    private $pdf;
    private $currentColumnWidths = []; // Declared property to store column widths

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
        if (headers_sent()) {
            throw new Exception('Some data has already been output, can\'t send PDF file');
        }

        $this->pdf = new \TCPDF($orientation, $unit, $format, true, 'UTF-8', false);
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor('AcadMeter');
        $this->pdf->SetTitle('AcadMeter Report');
        $this->pdf->SetSubject('Report');
        $this->pdf->SetMargins(15, 20, 15);
        $this->pdf->SetHeaderMargin(5);
        $this->pdf->SetFooterMargin(10);
        $this->pdf->SetAutoPageBreak(TRUE, 15);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFillColor(255, 255, 255);
    }

    public function addPage() {
        $this->pdf->AddPage();
    }

    public function setFont($family, $style, $size) {
        $this->pdf->SetFont($family, $style, $size);
    }

    public function addCell($width, $height, $text, $border = 0, $ln = 0, $align = '', $fill = false) {
        $this->pdf->Cell($width, $height, $text, $border, $ln, $align, $fill, '', 0, false, 'T', 'M');
    }

    public function addMultiCellCustom($width, $height, $text, $border = 0, $align = 'L', $fill = false, $ln = 1) {
        $this->pdf->MultiCell(
            $width,
            $height,
            $text,
            $border,
            $align,
            $fill,
            $ln,
            '',
            '',
            true,
            0,
            false,
            true,
            0,
            'T',
            false
        );
    }

    public function ln($height = null) {
        $this->pdf->Ln($height);
    }

    /**
     * Adds a table header with dynamic column widths based on content.
     *
     * @param array $headers Array of header labels.
     * @param array $columnWidths Array of column widths (optional).
     */
    public function addTableHeader($headers, $columnWidths = []) {
        $this->setFont('helvetica', 'B', 10);
        $this->pdf->SetFillColor(200, 200, 200);

        // Calculate total width available
        $totalWidth = $this->pdf->getPageWidth() - $this->pdf->getMargins()['left'] - $this->pdf->getMargins()['right'];

        // If no specific column widths provided, calculate based on content
        if (empty($columnWidths)) {
            $columnCount = count($headers);
            $columnWidths = [];
            foreach ($headers as $header) {
                $width = $this->pdf->GetStringWidth($header) + 10; // Add padding
                $columnWidths[] = $width;
            }
            // Adjust columns to fit the page
            $currentTotal = array_sum($columnWidths);
            if ($currentTotal < $totalWidth) {
                $extra = ($totalWidth - $currentTotal) / count($columnWidths);
                foreach ($columnWidths as &$width) {
                    $width += $extra;
                }
            } elseif ($currentTotal > $totalWidth) {
                $scalingFactor = $totalWidth / $currentTotal;
                foreach ($columnWidths as &$width) {
                    $width *= $scalingFactor;
                }
            }
        }

        // Store for later use
        $this->currentColumnWidths = $columnWidths;

        foreach ($headers as $index => $header) {
            $width = isset($columnWidths[$index]) ? $columnWidths[$index] : 30;
            $this->addCell($width, 8, $header, 1, 0, 'C', true);
        }
        $this->ln();
    }

    /**
     * Adds a table row with dynamic content and column widths.
     *
     * @param array $data Array of cell data.
     * @param array $columnWidths Array of column widths.
     */
    public function addTableRow($data, $columnWidths) {
        $this->setFont('helvetica', '', 10);
        $this->pdf->SetFillColor(255, 255, 255);
        foreach ($data as $index => $cell) {
            $width = isset($columnWidths[$index]) ? $columnWidths[$index] : 30;
            $this->pdf->MultiCell(
                $width,
                8,
                $cell,
                1,
                'C',
                false,
                0,
                '',
                '',
                true,
                0,
                false,
                true,
                0,
                'M',
                true
            );
        }
        $this->ln();
    }

    /**
     * Creates a report card for a student.
     *
     * @param array $student Associative array containing student data.
     * @param string $sectionName Name of the section.
     * @param int $rank Student's rank in the class.
     */
    public function createReportCard($student, $sectionName, $rank) {
        // Add Header
        $this->setFont('helvetica', 'B', 16);
        $this->addCell(0, 10, "AcadMeter REPORT CARD", 0, 1, 'C');
        $this->setFont('helvetica', 'B', 12);
        $this->addCell(0, 8, "School: City of Balanga National High School", 0, 1, 'C');
        $this->addCell(0, 8, "Section: {$sectionName}", 0, 1, 'C');
        $this->ln(5);

        // Student Information
        $this->setFont('helvetica', 'B', 12);
        $this->addCell(30, 8, "Name:", 0, 0, 'L');
        $this->setFont('helvetica', '', 12);
        $this->addCell(60, 8, "{$student['name']}", 0, 0, 'L');
        $this->setFont('helvetica', 'B', 12);
        $this->addCell(30, 8, "Rank:", 0, 0, 'L');
        $this->setFont('helvetica', '', 12);
        $this->addCell(0, 8, "{$rank}", 0, 1, 'L');
        $this->ln(5);

        // Dynamic Column Widths
        $headers = ['Materials Learned', 'Quarter I', 'Quarter II', 'Quarter III', 'Quarter IV', 'Final Grade', 'Remarks'];
        $columnWidths = $this->calculateColumnWidths($headers);

        // Grades Table Headers
        $this->addTableHeader($headers, $columnWidths);

        // Grades Table Rows
        foreach ($student['grades'] as $subject => $data) {
            $remarks = $this->getRemarks(floatval($data['final_grade']));
            $rowData = [
                $subject,
                $data['quarters'][1],
                $data['quarters'][2],
                $data['quarters'][3],
                $data['quarters'][4],
                $data['final_grade'],
                $remarks
            ];
            $this->addTableRow($rowData, $columnWidths);
        }

        // General Average
        $this->setFont('helvetica', 'B', 12);
        $this->addCell(0, 8, "GENERAL AVERAGE: {$student['general_average']}", 1, 1, 'R');
        $this->ln(5);

        // Grading Scale
        $this->setFont('helvetica', 'B', 12);
        $this->addCell(0, 8, "Grading Scale:", 0, 1, 'L');
        $this->setFont('helvetica', '', 10);
        $gradingScale = [
            'Outstanding (90-100) - Passed',
            'Very Satisfactory (85-89) - Passed',
            'Satisfactory (80-84) - Passed',
            'Fairly Satisfactory (75-79) - Passed',
            'Did Not Meet Expectations (Below 75) - Failed',
        ];
        foreach ($gradingScale as $scale) {
            $this->addMultiCellCustom(0, 6, $scale, 0, 'L');
        }

        // Footer
        $this->ln(10);
        $this->setFont('helvetica', '', 10);
        $this->addCell(100, 8, "Class Adviser: ____________________", 0, 0, 'L');
        $this->addCell(0, 8, "Date: ________________", 0, 1, 'R');
    }

    private function getRemarks($grade) {
        if ($grade >= 90 && $grade <= 100) {
            return 'Outstanding';
        } elseif ($grade >= 85 && $grade <= 89) {
            return 'Very Satisfactory';
        } elseif ($grade >= 80 && $grade <= 84) {
            return 'Satisfactory';
        } elseif ($grade >= 75 && $grade <= 79) {
            return 'Fairly Satisfactory';
        } else {
            return 'Did Not Meet Expectations';
        }
    }

    public function outputPDF($filename, $dest = 'I') {
        $this->pdf->Output($filename, $dest);
    }

    /**
     * Adds a polished bar graph using TCPDF's drawing functions.
     *
     * @param array $data Associative array of labels and values.
     * @param string $title Title of the graph.
     */
    public function addGraph($data, $title = '') {
        $this->pdf->Ln(10);

        // Define chart parameters
        $chartWidth = 180;
        $chartHeight = 80;
        $x = ($this->pdf->getPageWidth() - $chartWidth) / 2;
        $y = $this->pdf->GetY();

        // Draw title
        $this->setFont('helvetica', 'B', 12);
        $this->addCell(0, 8, $title, 0, 1, 'C');
        $this->ln(2);

        // Calculate maximum value for scaling
        $maxValue = max($data);
        $barWidth = ($chartWidth - (count($data) + 1) * 5) / count($data);
        $scale = ($chartHeight - 20) / $maxValue;

        // Draw bars
        $currentX = $x + 5;
        foreach ($data as $label => $value) {
            $barHeight = $value * $scale;
            // Draw bar
            $this->pdf->SetFillColor(54, 162, 235); // Blue color
            $this->pdf->Rect($currentX, $y + $chartHeight - $barHeight, $barWidth, $barHeight, 'DF');
            // Draw label
            $this->setFont('helvetica', '', 8);
            $this->pdf->SetXY($currentX, $y + $chartHeight + 2);
            $this->pdf->MultiCell($barWidth, 4, $label, 0, 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            // Draw value
            $this->pdf->SetXY($currentX, $y + $chartHeight - $barHeight - 5);
            $this->pdf->MultiCell($barWidth, 4, $value, 0, 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            $currentX += $barWidth + 5;
        }

        $this->ln($chartHeight + 10);
    }

    /**
     * Calculates dynamic column widths based on header labels and content.
     *
     * @param array $headers Array of header labels.
     * @return array Array of calculated column widths.
     */
    public function calculateColumnWidths($headers) {
        // Adjust widths based on content length
        $totalWidth = $this->pdf->getPageWidth() - $this->pdf->getMargins()['left'] - $this->pdf->getMargins()['right'];
        $columnWidths = [];

        foreach ($headers as $header) {
            $width = $this->pdf->GetStringWidth($header) + 10; // Add padding
            $columnWidths[] = $width;
        }

        // Normalize to fit the page
        $currentTotal = array_sum($columnWidths);
        if ($currentTotal < $totalWidth) {
            $extra = ($totalWidth - $currentTotal) / count($columnWidths);
            foreach ($columnWidths as &$width) {
                $width += $extra;
            }
        } elseif ($currentTotal > $totalWidth) {
            $scalingFactor = $totalWidth / $currentTotal;
            foreach ($columnWidths as &$width) {
                $width *= $scalingFactor;
            }
        }

        return $columnWidths;
    }
}
?>
