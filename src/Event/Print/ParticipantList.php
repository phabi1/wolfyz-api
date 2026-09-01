<?php

namespace App\Event\Print;

use Fpdf\Fpdf;

class ParticipantList
{
    const DAY_WIDTH = 10; // Width of each day column in mm

    private $participants = [];
    private $days = 1;

    private $title = 'Participant List';

    public function getParticipants()
    {
        return $this->participants;
    }

    public function setParticipants($participants)
    {
        $this->participants = $participants;
        return $this;
    }

    public function getDays()
    {
        return $this->days;
    }

    public function setDays($days)
    {
        $this->days = $days;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }


    public function render()
    {

        $pdf = new Fpdf();

        $pageWidth = $pdf->GetPageWidth() - 20; // 10mm margin on each side
        $dayTotalWidth = $this->days * self::DAY_WIDTH;
        $phoneWidth = 30; // Width of the phone column
        $nameWidth = $pageWidth - $dayTotalWidth - $phoneWidth;

        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $this->decodeString($this->title), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('Arial', '', 12);
        foreach ($this->participants as $participant) {
            $name = $this->decodeString($participant->firstname . ' ' . $participant->lastname);
            $phone = $participant->fields->telephone ?? '';
            
            $pdf->Cell($nameWidth, 10, $name, 1);
            $pdf->Cell($phoneWidth, 10, $phone, 1);

            for ($i = 1; $i <= $this->days; $i++) {
                $pdf->Cell(self::DAY_WIDTH, 10, '', 1);
            }

            $pdf->Ln();
        }

        return $pdf->Output('S');
    }

    private function decodeString($string)
    {
        return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
    }
}