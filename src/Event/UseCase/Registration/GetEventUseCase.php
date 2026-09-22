<?php

namespace App\Event\UseCase\Registration;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;

class GetEventUseCase implements UseCaseInterface
{
    private $eventRepository;

    private $participantFieldRepository;

    private $ticketRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->eventRepository = $entityManager->getRepository('wolf-events.event');

        $this->ticketRepository = $entityManager->getRepository('wolf-events.ticket');

        $this->participantFieldRepository = $entityManager->getRepository('wolf-events.participant-field');
    }

    public function execute(array $params = [])
    {
        $id = $params['id'] ?? null;

        if (!$id) {
            throw new \InvalidArgumentException('ID is required');
        }

        $event = $this->eventRepository->findById($id);

        if (!$event) {
            throw new \RuntimeException('Event not found');
        }

        $fields = $this->participantFieldRepository->find(['event_id' => ['eq' => $id]]);

        $tickets = $this->ticketRepository->find(['event_id' => ['eq' => $id]]);
        foreach ($tickets as &$ticket) {
            $ticket->fields = [];

            foreach ($fields as $field) {
                if (in_array($ticket->id, $field->tickets ?? [])) {
                    $ticket->fields[] = $field;
                }
            }
        }

        return [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'event_start' => $event->event_start,
                'event_end' => $event->event_end,
                'registration_start' => $event->registration_start,
                'registration_end' => $event->registration_end,
                'participant_nb' => $event->participant_nb,
                'participant_max' => $event->participant_max
            ],
            'tickets' => $tickets,
        ];
    }
}