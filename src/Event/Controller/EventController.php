<?php

namespace App\Event\Controller;

use App\Core\Mvc\Controller\EntityController;

class EventController extends EntityController
{
    protected $entityName = 'wolf-events.event';

    public function printParticipantsAction($request)
    {
        $id = $this->getIdentifierValue($request);
        return $this->get('use-case-bus')->execute('wolf-events.print_participants', ['eventId' => $id]);
    }

    public function amountAction ($request)
    {
        $eventId = $request->get_param('id');
        $useCaseBus = $this->getService('use-case-bus');
        $amount = $useCaseBus->execute('wolf-events.get_amount_for_event', ['event_id' => $eventId]);
        return ['amount' => $amount];
    }
}