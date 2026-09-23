<?php

namespace App\Event\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;

class CopyEventUseCase implements UseCaseInterface
{
    private $eventRepository;

    private $sessionRepository;

    private $ticketRepository;

    private $participantFieldRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->eventRepository = $entityManager->getRepository('wolf-events.event');
        $this->sessionRepository = $entityManager->getRepository('wolf-events.session');
        $this->ticketRepository = $entityManager->getRepository('wolf-events.ticket');
        $this->participantFieldRepository = $entityManager->getRepository('wolf-events.participant-field');
    }

    public function execute(array $params = [])
    {
        $eventId = (int) ($params['event_id'] ?? 0);
        if ($eventId <= 0) {
            throw new \InvalidArgumentException('The "event_id" parameter is required.');
        }

        $sourceEvent = $this->eventRepository->findById($eventId);
        if (!$sourceEvent) {
            throw new \RuntimeException(sprintf('Event with ID %d was not found.', $eventId));
        }

        $title = trim((string) ($params['title'] ?? ''));
        if ($title === '') {
            $title = sprintf('%s (copy)', $sourceEvent->title);
        }

        $copiedEvent = $this->eventRepository->insert([
            'title' => $title,
            'slug' => null,
            'event_type' => $sourceEvent->event_type,
            'event_start' => $sourceEvent->event_start,
            'event_end' => $sourceEvent->event_end,
            'registration_start' => $sourceEvent->registration_start,
            'registration_end' => $sourceEvent->registration_end,
            'participant_max' => $sourceEvent->participant_max,
        ]);

        $sourceTickets = $this->ticketRepository->find(['event_id' => ['eq' => $eventId]]);
        $ticketIdMap = [];

        foreach ($sourceTickets as $ticket) {
            $createdTicket = $this->ticketRepository->insert([
                'event_id' => $copiedEvent->id,
                'title' => $ticket->title,
                'amount' => $ticket->amount,
                'member_only' => $ticket->member_only,
                'participant_max' => $ticket->participant_max,
                'weight' => $ticket->weight,
            ]);

            $ticketIdMap[(int) $ticket->id] = (int) $createdTicket->id;
        }

        $sourceSessions = $this->sessionRepository->find(['event_id' => ['eq' => $eventId]]);
        foreach ($sourceSessions as $session) {
            $this->sessionRepository->insert([
                'event_id' => $copiedEvent->id,
                'session_start' => $session->session_start,
                'session_end' => $session->session_end,
            ]);
        }

        $sourceParticipantFields = $this->participantFieldRepository->find(['event_id' => ['eq' => $eventId]]);
        foreach ($sourceParticipantFields as $field) {
            $mappedTickets = [];
            foreach ($field->tickets ?? [] as $ticketId) {
                $ticketIdAsInt = (int) $ticketId;
                if (isset($ticketIdMap[$ticketIdAsInt])) {
                    $mappedTickets[] = $ticketIdMap[$ticketIdAsInt];
                }
            }

            $this->participantFieldRepository->insert([
                'event_id' => $copiedEvent->id,
                'label' => $field->label,
                'type' => $field->type,
                'description' => $field->description,
                'options' => $field->options,
                'required' => $field->required,
                'tickets' => $mappedTickets,
                'weight' => $field->weight,
            ]);
        }

        return $this->eventRepository->findById($copiedEvent->id);
    }
}
