<?php

namespace App\Membership\UseCase\Subscription\Export;

class XlsxExport implements ExportInterface {
    public function export(array $headers, array &$data): string
    {
        $file = tempnam(sys_get_temp_dir(), 'subscriptions_export_') . '.xlsx';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write header
        $sheet->fromArray($headers, null, 'A1');

        // Write data
        $sheet->fromArray($data, null, 'A2');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($file);

        return $file;
    }
}