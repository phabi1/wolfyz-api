<?php

namespace App\HelloAsso\Webhook\Handler;

use App\Core\Events\EventDispatcher;
use App\Core\Helper\StringHelper;
use App\HelloAsso\Event\OrderSuccessEvent;

class ReceiveOrderHandler implements WebhookHandlerInterface
{
    private $stringHelper;

    private EventDispatcher $eventDispatcher;

    public function __construct(
        StringHelper $stringHelper,
        EventDispatcher $eventDispatcher
    ) {
        $this->stringHelper = $stringHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle(array $payload = []): void
    {
        $externalId = $payload['metadata']['external_id'] ?? '';
        $this->eventDispatcher->dispatch(OrderSuccessEvent::EVENT, new OrderSuccessEvent($externalId));
    }
}