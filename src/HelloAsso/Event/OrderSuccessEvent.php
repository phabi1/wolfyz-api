<?php

namespace App\HelloAsso\Event;

use App\Core\Events\Event;

class OrderSuccessEvent extends Event
{
    const EVENT = 'order.success';

    private string $externalId;

    public function __construct(string $externalId)
    {
        $this->externalId = $externalId;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }
}