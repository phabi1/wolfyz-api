<?php

namespace App\Membership\Event;

use App\Core\Events\Event;

class RequestStatusChangedEvent extends Event
{
    const EVENT = 'wolf-memberships.request.status_changed';

    private \stdClass $request;

    private string|null $previousStatus = null;

    private string $status;

    public function __construct(\stdClass $request, string $status, ?string $previousStatus = null)
    {
        $this->request = $request;
        $this->status = $status;
        $this->previousStatus = $previousStatus;
    }

    public function getRequest(): \stdClass
    {
        return $this->request;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPreviousStatus(): ?string
    {
        return $this->previousStatus;
    }
}