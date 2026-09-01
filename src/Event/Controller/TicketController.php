<?php

namespace App\Event\Controller;

use App\Core\Mvc\Controller\EntityController;

class TicketController extends EntityController
{
    protected $entityName = 'wolf-events.ticket';

    protected function buildFilters($request)
    {
        $filters = parent::buildFilters($request);
        if ($request->get_param('event_id')) {
            $filters['event_id'] = ['eq' => (int) $request->get_param('event_id')];
        }
        return $filters;
    }

    protected function prepareDataFromRequest(array $body, \WP_REST_Request $request)
    {
        $data = parent::prepareDataFromRequest($body, $request);

        $data['event_id'] = (int) $request->get_param('event_id');
        return $data;
    }
}