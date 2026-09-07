<?php

namespace App\Membership\Listener;

use App\Core\Events\Event;
use App\Core\UseCase\UseCaseBus;
use App\Membership\Event\RequestStatusChangedEvent;

class RequestStatusChangedListener
{
    private UseCaseBus  $useCaseBus;

    public function __construct(UseCaseBus $useCaseBus)
    {
        $this->useCaseBus = $useCaseBus;
    }

    public function onStatusChanged(RequestStatusChangedEvent $event): void
    {
        switch ($event->getStatus()) {
            case 'pending':
                $this->onPending($event);
                break;
            case 'approved':
                $this->onApproved($event);
                break;
            case 'rejected':
                $this->onRejected($event);
                break;
            case 'cancelled':
                $this->onCancelled($event);
                break;
            case 'paid':
                $this->onPaid($event);
                break;
        }
    }

    private function onPending(RequestStatusChangedEvent $event): void
    {
        // Handle the request status changed to pending event
    }

    private function onApproved(RequestStatusChangedEvent $event): void
    {
        $request = $event->getRequest();
        $this->useCaseBus->execute('wolf-memberships.convert_request_to_subscriptions', [
            'campaign_id' => $request->campaign_id,
            'request_id' => $request->id,
        ]);
    }

    private function onRejected(RequestStatusChangedEvent $event): void
    {
        // Rejected status hook intentionally left as a no-op for now.
    }

    private function onCancelled(RequestStatusChangedEvent $event): void
    {
        // Handle the request status changed to cancelled event
    }

    private function onPaid(RequestStatusChangedEvent $event): void
    {
        // Handle the request status changed to paid event
    }
}