<?php

namespace App\Event\Controller;

use App\Core\Mvc\Controller\EntityController;
use App\Event\Entity\Service\ParticipantEntityService;
use Symfony\Component\HttpFoundation\Request;

class ParticipantController extends EntityController
{
    public function __construct(
        ParticipantEntityService $entityService
    ) {
        $this->entityService = $entityService;
    }

    protected function buildFilters($request)
    {
        $filters = parent::buildFilters($request);
        if ($request->attributes->get('event_id')) {
            $filters['event_id'] = ['eq' => (int) $request->attributes->get('event_id')];
        }
        return $filters;
    }

    protected function prepareDataFromRequest(array $data, Request $request)
    {
        $prepared = parent::prepareDataFromRequest($data, $request);
        $prepared['event_id'] = (int) $request->attributes->get('event_id');
        return $prepared;
    }

    public function printAction(Request $request)
    {
        $eventId = (int) $request->attributes->get('event_id');
        $res = $this->getService('use-case-bus')->execute('wolf-events.print_participants', ['eventId' => $eventId, 'days' => 5]);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="' . $res['filename'] . '"');

        echo base64_decode($res['pdf']);
        exit;
    }
}