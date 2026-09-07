<?php

declare(strict_types=1);

namespace App\Test\Membership\Listener;

use App\Core\Events\Event;
use App\Membership\Event\RequestStatusChangedEvent;
use App\Membership\Listener\RequestStatusChangedListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestStatusChangedListenerTest extends TestCase
{
    #[DataProvider('statusProvider')]
    public function testOnStatusChangedRoutesToExpectedHandler(string $status, string $expectedCall): void
    {
        $listener = new SpyRequestStatusChangedListener();
        $event = new RequestStatusChangedEvent((object) ['id' => 1], $status);

        $listener->onStatusChanged($event);

        self::assertSame([$expectedCall], $listener->calls);
    }

    public function testOnStatusChangedWithUnknownStatusDoesNothing(): void
    {
        $listener = new SpyRequestStatusChangedListener();
        $event = new RequestStatusChangedEvent((object) ['id' => 1], 'unknown');

        $listener->onStatusChanged($event);

        self::assertSame([], $listener->calls);
    }

    public static function statusProvider(): array
    {
        return [
            'pending' => ['pending', 'pending'],
            'approved' => ['approved', 'approved'],
            'rejected' => ['rejected', 'rejected'],
            'cancelled' => ['cancelled', 'cancelled'],
            'paid' => ['paid', 'paid'],
        ];
    }
}

final class SpyRequestStatusChangedListener extends RequestStatusChangedListener
{
    /** @var string[] */
    public array $calls = [];

    public function onPending(Event $event): void
    {
        $this->calls[] = 'pending';
    }

    public function onApproved(Event $event): void
    {
        $this->calls[] = 'approved';
    }

    public function onRejected(Event $event): void
    {
        $this->calls[] = 'rejected';
    }

    public function onCancelled(Event $event): void
    {
        $this->calls[] = 'cancelled';
    }

    public function onPaid(Event $event): void
    {
        $this->calls[] = 'paid';
    }
}
