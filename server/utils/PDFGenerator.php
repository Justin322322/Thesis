<?php
// server/utils/PDFGenerator.php

require_once __DIR__ . '/../../vendor/autoload.php';

class PDFGenerator {
    private $pdf;

    public function __construct() {
        $this->pdf = new \TCPDF();
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor('AcadMeter');
        $this->pdf->SetTitle('Report');
        $this->pdf->SetSubject('Report');
        $this->pdf->SetMargins(15, 16, 15);
        $this->pdf->SetHeaderMargin(9);
        $this->pdf->SetFooterMargin(9);
        $this->pdf->SetAutoPageBreak(TRUE, 16);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
    }

    public function generateHeader($title) {
        return "
            <div style='text-align: center;'>
                <h2>{$title}</h2>
                <hr>
            </div>
        ";
    }

    public function generateReport($content, $filename) {
        $this->pdf->AddPage();
        $this->pdf->writeHTML($content, true, false, true, false, '');
        $this->pdf->Output($filename, 'D');
    }
}