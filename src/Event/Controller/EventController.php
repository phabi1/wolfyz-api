<?php

namespace App\Event\Controller;

use App\Core\Mvc\Controller\EntityController;
use Symfony\Component\HttpFoundation\Request;

class EventController extends EntityController
{
    protected $entityName = 'wolf-events.event';

    public function copyAction(Request $request)
    {
        $eventId = (int) $this->getIdentifierValue($request);
        $payload = $request->getPayload()->all();
        $title = isset($payload['title']) ? trim((string) $payload['title']) : '';

        $newEvent = $this->useCaseBus('wolf-events.copy_event', [
            'event_id' => $eventId,
            'title' => $title,
        ]);
        return ['id' => $newEvent->id];
    }

    public function printParticipantsAction($request)
    {
        $id = $this->getIdentifierValue($request);
        return $this->useCaseBus('wolf-events.print_participants', ['eventId' => $id]);
    }

    public function amountAction ($request)
    {
        $eventId = $request->get_param('id');
        $useCaseBus = $this->getService('use-case-bus');
        $amount = $useCaseBus->execute('wolf-events.get_amount_for_event', ['event_id' => $eventId]);
        return ['amount' => $amount];
    }
}