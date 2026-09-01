<?php

namespace App\Membership\Dashboard;

interface SourceBusInterface
{
    public function source(array $data = []): array;

}