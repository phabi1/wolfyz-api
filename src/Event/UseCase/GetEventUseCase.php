<?php

namespace App\Event\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;

class GetEventUseCase implements UseCaseInterface
{
    private $eventRepository;

    private $ticketRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->eventRepository = $entityManager->getRepository('wolf-events.event');

        $this->ticketRepository = $entityManager->getRepository('wolf-events.ticket');
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

        $event->tickets = $this->ticketRepository->find(['event_id' => ['eq' => $id]]);

        return $event;
    }
}