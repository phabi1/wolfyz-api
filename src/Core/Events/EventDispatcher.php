<?php

namespace App\Core\Events;

class EventDispatcher {
    public function dispatch(string $eventName, array $eventData = []): void
    {
        // Dispatch the event to all registered listeners
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                call_user_func($listener, $eventData);
            }
        }
    }

    private $listeners = [];

    public function addListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }
        $this->listeners[$eventName][] = $listener;
    }
}