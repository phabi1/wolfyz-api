<?php

namespace App\Core\Events;

abstract class Event
{
    private $stopped = false;

    public function stop(): void
    {
        $this->stopped = true;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }
}