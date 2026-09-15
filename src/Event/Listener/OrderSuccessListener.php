<?php

namespace App\Event\Listener;

use App\Core\Di\Locator;
use App\Core\Helper\StringHelper;
use App\Core\UseCase\UseCaseBus;
use App\HelloAsso\Event\OrderSuccessEvent;

class OrderSuccessListener
{
    private StringHelper $stringHelper;

    private UseCaseBus $useCaseBus;

    public function __construct(UseCaseBus $useCaseBus, Locator $helpers)
    {
        $this->useCaseBus = $useCaseBus;
        $this->stringHelper = $helpers->get('string');
    }

    public function onOrderSuccess(OrderSuccessEvent $event)
    {
        $externalId = $event->getExternalId();
        if ($this->stringHelper->startsWith($externalId, 'event:')) {
            $checkoutId = substr($externalId, strlen('event:'));

            $this->useCaseBus->execute('wolf-events.paid_checkout', [
                'id' => $checkoutId,
                'payed_at' => $event->getPayedAt()
            ]);
        }
    }
}