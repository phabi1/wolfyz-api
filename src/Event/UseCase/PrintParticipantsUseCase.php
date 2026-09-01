<?php

namespace App\Event\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepository;
use App\Core\UseCase\UseCaseInterface;
use App\Event\Entity\Repository\ParticipantRepositoryInterface;
use App\Event\Print\ParticipantList;
use App\Event\Entity\Repository\ParticipantRespository;
use App\Event\Entity\Repository\SessionRepository;

class PrintParticipantsUseCase implements UseCaseInterface
{
    private EntityRepository $eventRepository;

    /**
     * Summary of SessionRepository
     * @var SessionRepository
     */
    private SessionRepository $sessionRepository;

    /**
     * Summary of participantRepository
     * @var ParticipantRepositoryInterface
     */
    private ParticipantRepositoryInterface $participantRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->eventRepository = $entityManager->getRepository('wolf-events.event');
        $this->participantRepository = $entityManager->getRepository('wolf-events.participant');
        $this->sessionRepository = $entityManager->getRepository('wolf-events.session');
    }

    public function execute(array $params = [])
    {
        $eventId = $params['eventId'];

        $event = $this->eventRepository->findById($eventId);
        $sessions = $this->sessionRepository->findByEventId($eventId);

        $days = count($sessions) ?? 1;

        $participants = $this->participantRepository->findByEventId($eventId);

        usort($participants, function ($a, $b) {
            return strcmp($a->firstname . ' ' . $a->lastname, $b->firstname . ' ' . $b->lastname);
        });

        $pdf = new ParticipantList();
        $pdf->setParticipants($participants);
        $pdf->setDays($days);
        $pdf->setTitle($event->title);
        
        $pdfContent = $pdf->render();
        return [
            'pdf' => base64_encode($pdfContent),
            'filename' => $this->generateFilename($event)
        ];
    }

    protected function generateFilename($event)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $event->title);
        return $filename . '.pdf';
    }
}