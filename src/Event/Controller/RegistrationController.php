<?php

namespace App\Event\Controller;

use App\Core\Mvc\Controller\AbstractController;

class RegistrationController extends AbstractController
{
    public function infoAction($request)
    {
        $eventId = (int) $request->get_param('event_id');
        $useCaseBus = $this->getService('use_case_bus');

        try {
            $event = $useCaseBus->execute('wolf-events.get_event', ['id' => $eventId]);
        } catch (\RuntimeException $e) {
            return new \WP_Error('event_not_found', 'Event not found', ['status' => 404]);
        }

        return [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'participant_fields' => $event->participant_fields ?? [],
            ],
            'tickets' => array_map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'amount' => $ticket->amount,
                    'participant_fields' => $ticket->participant_fields ?? [],
                    'participant_max' => $ticket->participant_max ?? null,
                    'participant_nb' => $ticket->participant_nb ?? 0,
                ];
            }, $event->tickets ?? []),
        ];
    }

    public function registerAction($request)
    {
        $eventId = (int) $request->get_param('event_id');
        $data = $request->get_json_params();

        $registration = $data['registration'] ?? [];

        if (empty($registration['firstname']) || empty($registration['lastname']) || empty($registration['email'])) {
            return new \WP_Error('invalid_data', 'Prénom, nom et email sont obligatoires', ['status' => 400]);
        }

        if (!is_email($registration['email'])) {
            return new \WP_Error('invalid_email', 'Adresse email invalide', ['status' => 400]);
        }

        $participants = $data['participants'] ?? [];
        if (empty($participants)) {
            return new \WP_Error('no_participants', 'Aucun participant fourni', ['status' => 400]);
        }

        foreach ($participants as $participant) {
            if (empty($participant['firstname']) || empty($participant['lastname'])) {
                return new \WP_Error('invalid_participant', 'Prénom et nom sont obligatoires pour chaque participant', ['status' => 400]);
            }
        }

        $useCaseBus = $this->getService('use_case_bus');

        try {
            $result = $useCaseBus->execute('wolf-events.register_to_event', [
                'event_id' => $eventId,
                'registration' => $registration,
                'participants' => $participants,
            ]);

            return [
                'success' => true,
                'message' => 'Inscription réussie',
                'payment_url' => $result['payment_url'] ?? null
            ];
        } catch (\Exception $e) {
            return new \WP_Error('registration_error', $e->getMessage(), ['status' => 400]);
        }


    }
}