<?php

namespace App\Core\Events;

use App\Core\Di\LazyService;

class EventDispatcher
{
    private array $listeners = [];

    public function dispatch(string $name, Event $event): void
    {
        if (isset($this->listeners[$name])) {
            usort($this->listeners[$name], function ($a, $b) {
                return $b[1] <=> $a[1];
            });

            foreach ($this->listeners[$name] as $listener) {
                $callable = $listener[0];


                if (is_array($callable) && $callable[0] instanceof LazyService) {
                    $callable = [($callable[0])(), $callable[1]];
                }

                call_user_func($callable, $event);
                if ($event->isStopped()) {
                    break;
                }
            }
        }
    }

    public function addListener(string $eventName, array $listener, int $priority = 0): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }
        $this->listeners[$eventName][] = [$listener, $priority];
    }
}