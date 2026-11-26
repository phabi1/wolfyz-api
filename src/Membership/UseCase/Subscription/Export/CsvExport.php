<?php

namespace App\Membership\UseCase\Subscription\Export;

class CsvExport implements ExportInterface {
    public function export(array $headers, array &$data): string
    {
        $file = tempnam(sys_get_temp_dir(), 'subscriptions_export_') . '.csv';

        $handle = fopen($file, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Could not open file for writing');
        }

        // Write header
        fputcsv($handle, $headers);

        // Write data
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $file;
    }
}