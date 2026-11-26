<?php

namespace App\Membership\UseCase\Subscription\Export;

interface ExportInterface
{
    public function export(array $headers, array &$data): string;
}