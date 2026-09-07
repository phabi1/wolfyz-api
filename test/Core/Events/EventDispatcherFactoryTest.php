<?php

declare(strict_types=1);

namespace App\Test\Core\Events;

use App\Core\Di\Container;
use App\Core\Events\Event;
use App\Core\Events\EventDispatcherFactory;
use PHPUnit\Framework\TestCase;

final class EventDispatcherFactoryTest extends TestCase
{
    public function testCreateRegistersTaggedListenerAndDispatchesEvent(): void
    {
        FactoryListener::$called = false;

        $container = new Container([
            'listener.service' => [
                'class' => FactoryListener::class,
                'tags' => [
                    [
                        'name' => 'event.listener',
                        'event' => 'demo.event',
                        'method' => 'onEvent',
                        'priority' => 5,
                    ],
                ],
            ],
        ]);

        $dispatcher = EventDispatcherFactory::create($container);
        $dispatcher->dispatch('demo.event', new FactoryEvent());

        self::assertTrue(FactoryListener::$called);
    }

    public function testCreateIgnoresInvalidEventTags(): void
    {
        FactoryListener::$called = false;

        $container = new Container([
            'listener.service' => [
                'class' => FactoryListener::class,
                'tags' => [
                    [
                        'name' => 'event.listener',
                        'event' => 'demo.event',
                    ],
                ],
            ],
        ]);

        $dispatcher = EventDispatcherFactory::create($container);
        $dispatcher->dispatch('demo.event', new FactoryEvent());

        self::assertFalse(FactoryListener::$called);
    }
}

final class FactoryEvent extends Event
{
}

final class FactoryListener
{
    public static bool $called = false;

    public function onEvent(FactoryEvent $event): void
    {
        self::$called = true;
    }
}
