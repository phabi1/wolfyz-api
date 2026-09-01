<?php

namespace App\Event\UseCase;

use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Event\Entity\Repository\EventRepository;
use App\Event\Entity\Repository\ParticipantRespository;
use App\Event\Entity\Repository\TicketRepository;

class DeleteParticipantUseCase implements UseCaseInterface
{
    private ParticipantRespository $participantRepository;
    private EventRepository $eventRepository;

    private TicketRepository $ticketRepository;

    public function __construct(
        EntityManager $entityManager,
    ) {
        $participantRepository = $entityManager->getRepository('wolf-events.participant');
        if (!($participantRepository instanceof ParticipantRespository)) {
            throw new \Exception("Participant repository must be instance of ParticipantRespository");
        }
        $this->participantRepository = $participantRepository;

        $eventRepository = $entityManager->getRepository('wolf-events.event');
        if (!($eventRepository instanceof EventRepository)) {
            throw new \Exception("Event repository must be instance of EventRepository");
        }
        $this->eventRepository = $eventRepository;

        $ticketRepository = $entityManager->getRepository('wolf-events.ticket');
        if (!($ticketRepository instanceof TicketRepository)) {
            throw new \Exception("Ticket repository must be instance of TicketRepository");
        }
        $this->ticketRepository = $ticketRepository;
    }

    public function execute(array $params = [])
    {
        $participant = $this->participantRepository->findById($params['id']);
        if (!$participant) {
            throw new \Exception('Participant not found');
        }

        $this->participantRepository->delete($participant->id);

        $this->eventRepository->updateParticipantCount($participant->event_id);
        $this->ticketRepository->updateParticipantCount($participant->ticket_id);

        return true;
    }
}