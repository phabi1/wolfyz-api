<?php

namespace App\Event\Controller;

use App\Core\Mvc\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends ApiController
{
    public function infoAction(Request $request)
    {
        $eventId = (int) $request->attributes->get('id');

        $response = $this->useCaseBus('wolf-events.get_event', ['id' => $eventId]);
        $event = $response['event'] ?? null;
        $tickets = $response['tickets'] ?? [];

        return [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'participant_fields' => $event->participant_fields ?? [],
                'participant_max' => $event->participant_max ?? null,
                'regisration_start' => $event->regisration_start ?? null,
                'registration_end' => $event->registration_end ?? null,
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
            }, $tickets ?? []),
        ];
    }

    public function registerAction(Request $request)
    {
        $eventId = (int) $request->attributes->get('id');
        $data = $request->getPayload()->all();

        $contact = $data['contact'] ?? [];

        $errors = [];

        if (empty($contact['firstname'])) {
            $errors['contact.firstname'] = 'required';
        }
        if (empty($contact['lastname'])) {
            $errors['contact.lastname'] = 'required';
        }
        if (empty($contact['email'])) {
            $errors['contact.email'] = 'required';
        }

        if (!empty($contact['email']) && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact.email'] = 'invalid';
        }

        $participants = $data['participants'] ?? [];
        if (empty($participants)) {
            $errors['participants'] = 'required';
        }

        foreach ($participants as $participantIndex => $participant) {
            if (empty($participant['firstname'])) {
                $errors['participants.' . $participantIndex . '.firstname'] = 'required';
            }
            if (empty($participant['lastname'])) {
                $errors['participants.' . $participantIndex . '.lastname'] = 'required';
            }

            if (empty($participant['ticket_id'])) {
                $errors['participants.' . $participantIndex . '.ticket_id'] = 'required';
            }

        }

        if (!empty($errors)) {
            return new JsonResponse(['error' => 'invalid_data', 'message' => $errors], 400);
        }

        try {
            $result = $this->useCaseBus('wolf-events.register_to_event', [
                'event_id' => $eventId,
                'contact' => $contact,
                'participants' => $participants,
                'message' => $data['message'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => 'Inscription réussie',
                'payment_url' => $result['payment_url'] ?? null
            ];
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'registration_error', 'message' => $e->getMessage()], 400);
        }


    }
}