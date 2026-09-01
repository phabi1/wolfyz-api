<?php

namespace App\Event\UseCase;

use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Event\Entity\Repository\TicketRepositoryInterface;

class CreateEventUseCase implements UseCaseInterface
{
    private $eventRepository;

    private TicketRepositoryInterface $ticketRepository;

    private $sessionRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->eventRepository = $entityManager->getRepository('wolf-events.event');
        $this->ticketRepository = $entityManager->getRepository('wolf-events.ticket');
        $this->sessionRepository = $entityManager->getRepository('wolf-events.session');
    }

    public function execute(array $params = [])
    {
        $data = [
            'title' => $params['title'] ?? 'Untitled Event',
            'slug' => $params['slug'] ?? null,
            'event_start' => $params['event_start'],
            'event_end' => $params['event_end'],
            'registration_start' => $params['registration_start'] ?? null,
            'registration_end' => $params['registration_end'] ?? null,
            'participant_max' => $params['participant_max'] ?? null,
            'participant_fields' => $params['participant_fields'] ?? []
        ];

        $event= $this->eventRepository->insert($data);

        if (!empty($params['sessions'])) {
            foreach ($params['sessions'] as $session) {
                $this->sessionRepository->insert([
                    'title' => $session['title'],
                    'session_start' => $session['session_start'],
                    'session_end' => $session['session_end'],
                    'event_id' => $event->id
                ]);
            }
        }

        if (!empty($params['tickets'])) {
            foreach ($params['tickets'] as $ticket) {
                $this->ticketRepository->insert([
                    'title' => $ticket['title'],
                    'amount' => $ticket['amount'],
                    'participant_fields' => $ticket['participant_fields'] ?? [],
                    'event_id' => $event->id
                ]);
            }
        }

        return $event;
    }
}