<?php

namespace App\Event\Controller;

use App\Core\Mvc\Controller\EntityController;
use Symfony\Component\HttpFoundation\Request;

class ParticipantFieldController extends EntityController
{
    protected $entityName = 'wolf-events.participant-field';

    protected function buildFilters($request)
    {
        $filters = parent::buildFilters($request);
        if ($request->attributes->get('event_id')) {
            $filters['event_id'] = ['eq' => (int) $request->attributes->get('event_id')];
        }
        return $filters;
    }

    protected function prepareDataFromRequest(array $body, Request $request)
    {
        $data = parent::prepareDataFromRequest($body, $request);

        $data['event_id'] = (int) $request->attributes->get('event_id');
        return $data;
    }
}