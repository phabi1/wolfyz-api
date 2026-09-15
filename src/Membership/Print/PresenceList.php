<?php

namespace App\Membership\Print;

use Fpdf\Fpdf;

class PresenceList
{
    private const PAGE_MARGIN = 8;
    private const DAY_WIDTH = 8;
    private const HEADER_HEIGHT = 6;
    private const ROW_HEIGHT = 7;

    protected $title = 'Presence List';

    protected $headers = [];

    private $period;

    private $lessons = [];

    private $members = [];

    public function __construct()
    {
        $this->headers = [
            [
                'data' => 'index',
                'label' => '',
                'width' => 5
            ],
            [
                'data' => 'lastname',
                'label' => 'Nom',
                'width' => 40
            ],
            [
                'data' => 'firstname',
                'label' => 'Prénom',
                'width' => 40
            ],
            [
                'data' => 'birthdate',
                'label' => 'Date de naissance',
                'width' => 30
            ],
            [
                'data' => 'phone',
                'label' => 'Téléphone',
                'width' => 30
            ],
            [
                'label' => 'Inscription',
                'width' => 30
            ],
            [
                'label' => 'Médical',
                'width' => 30
            ],
            [
                'label' => 'Autorisation parentale',
                'width' => 30
            ]
        ];
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    public function setLessons(array $lessons)
    {
        $this->lessons = $lessons;
        return $this;
    }

    public function setMembers(array $members)
    {
        $this->members = $members;
        return $this;
    }

    public function setPeriod($period)
    {
        $this->period = $period;
        return $this;
    }

    public function render()
    {
        $pdf = new Fpdf('L', 'mm', 'A4');
        $pdf->SetMargins(self::PAGE_MARGIN, self::PAGE_MARGIN, self::PAGE_MARGIN);
        $pdf->SetAutoPageBreak(true, self::PAGE_MARGIN);

        foreach ($this->lessons as $lesson) {

            if (count($this->members[$lesson->id] ?? []) === 0) {
                continue;
            }

            $pdf->AddPage('L', 'A4');

            $headers = $this->buildHeaders($lesson);

            $this->renderHeader($pdf, $lesson);

            $pdf->Ln(3);


            $this->renderMembers($pdf, $headers, $this->members[$lesson->id] ?? []);
        }

        return $pdf->Output('S', '', true);
    }


    private function buildHeaders($lesson)
    {
        $headers = $this->headers;

        // Count days into period
        $periodStart = $this->period->start_date;
        $periodEnd = $this->period->end_date;

        $day = $lesson->day;

        $days = [];
        $currentDate = $periodStart;
        while ($currentDate <= $periodEnd) {
            if (date('N', $currentDate) == $day) {
                $days[] = $currentDate;
            }
            $currentDate = strtotime('+1 day', $currentDate);
        }

        $delimiter = 'phone';
        $delimiterIndex = array_search($delimiter, array_column($headers, 'data'));

        $firstPart = array_slice($headers, 0, $delimiterIndex + 1);
        $lastPart = array_slice($headers, $delimiterIndex + 1);

        $dayHeaders = array_map(function ($date) {
            return [
                'label' => date('d/m', $date),
                'width' => self::DAY_WIDTH,
            ];
        }, $days);

        return array_merge($firstPart, $dayHeaders, $lastPart);

    }
    private function renderHeader(Fpdf $pdf, $lesson)
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, $this->decodeString($lesson->title), 0, 1, 'C');

        $subTitle = '' . $this->getDay($lesson->day) . ' - ' . date('H:i', $lesson->lesson_start) . ' à ' . date('H:i', $lesson->lesson_end);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 6, $this->decodeString($subTitle), 0, 1, 'C');
        $pdf->Ln(1);

        $y = $pdf->GetY();
        $x = self::PAGE_MARGIN;

        $pdf->SetLineWidth(0.1);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY($x, $y);
        $pdf->Cell(28, 6, $this->decodeString('Présences'), 1, 0, 'C');
        $pdf->Cell(3, 6, '', 0, 0);
        $pdf->Cell(120, 6, $this->decodeString('Professeur'), 1, 1, 'L');
        $pdf->SetLineWidth(0);

        $pdf->Ln(2);
    }

    private function renderMembers(Fpdf $pdf, $headers, $members)
    {
        $pdf->SetFont('Arial', 'B', 8);
        foreach ($headers as $header) {
            $pdf->Cell($header['width'] ?? 30, self::HEADER_HEIGHT, $this->decodeString($header['label']), 1, 0, 'C');
        }
        $pdf->Ln();

        $rows = $this->transformMembersToRows($members, $headers);

        $pdf->SetFont('Arial', '', 10);
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $align = is_numeric($cell['label']) ? 'C' : 'L';
                $pdf->Cell($cell['width'], self::ROW_HEIGHT, $cell['label'], 1, 0, $align);
            }
            $pdf->Ln();
        }
    }

    private function transformMembersToRows(array $members, array $headers)
    {
        $index = 1;
        $rows = [];
        foreach ($members as $member) {
            $row = [];
            foreach ($headers as $header) {
                $cell = [
                    'width' => $header['width'] ?? 30,
                    'label' => ''
                ];
                if ($header['data'] ?? null) {
                    switch ($header['data']) {
                        case 'index':
                            $cell['label'] = $index;
                            break;
                        default:
                            $value = $member->{$header['data']} ?? '';
                            if (is_string($value)) {
                                $value = $this->decodeString($value);
                            }
                            if ($header['data'] === 'birthdate' && $value) {
                                $value = date('d-m-Y', $value);
                            }
                            $cell['label'] = $value;
                            break;
                    }
                }


                $row[] = $cell;
            }
            $rows[] = $row;
            $index++;
        }
        return $rows;
    }

    private function getDay($day)
    {
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];
        return $days[$day] ?? '';
    }

    private function decodeString($string)
    {
        return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
    }
}