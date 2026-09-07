<?php

declare(strict_types=1);

namespace App\Test\Core\Events;

use App\Core\Events\Event;
use App\Core\Events\EventDispatcher;
use PHPUnit\Framework\TestCase;

final class EventDispatcherTest extends TestCase
{
    public function testDispatchCallsListenersByPriorityDescending(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new TestEvent();

        $dispatcher->addListener('demo.event', [new RecordingListener('low', $event), 'onEvent'], 1);
        $dispatcher->addListener('demo.event', [new RecordingListener('high', $event), 'onEvent'], 10);

        $dispatcher->dispatch('demo.event', $event);

        self::assertSame(['high', 'low'], $event->calls);
    }

    public function testDispatchStopsPropagationWhenEventIsStopped(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new TestEvent();

        $dispatcher->addListener('demo.event', [new StoppingListener($event), 'onEvent'], 10);
        $dispatcher->addListener('demo.event', [new RecordingListener('after-stop', $event), 'onEvent'], 1);

        $dispatcher->dispatch('demo.event', $event);

        self::assertSame(['stopped'], $event->calls);
    }

    public function testDispatchWithUnknownEventDoesNothing(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new TestEvent();

        $dispatcher->dispatch('unknown.event', $event);

        self::assertSame([], $event->calls);
    }
}

final class TestEvent extends Event
{
    /** @var string[] */
    public array $calls = [];
}

final class RecordingListener
{
    public function __construct(private string $name, private TestEvent $event)
    {
    }

    public function onEvent(TestEvent $event): void
    {
        $this->event->calls[] = $this->name;
    }
}

final class StoppingListener
{
    public function __construct(private TestEvent $event)
    {
    }

    public function onEvent(TestEvent $event): void
    {
        $this->event->calls[] = 'stopped';
        $event->stop();
    }
}
