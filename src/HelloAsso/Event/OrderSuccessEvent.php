<?php

namespace App\HelloAsso\Event;

use App\Core\Events\Event;

class OrderSuccessEvent extends Event
{
    const EVENT = 'order.success';

    private string $externalId;

    private int $payed_at;

    public function __construct(string $externalId, ?int $payed_at = null)
    {
        $this->externalId = $externalId;
        $this->payed_at = $payed_at ?? time();
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getPayedAt(): int
    {
        return $this->payed_at;
    }
}