<?php

namespace App\Event\Entity\Service;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityService;
use App\Core\UseCase\UseCaseBus;

class ParticipantEntityService extends EntityService
{
    private UseCaseBus $useCaseBus;


    public function __construct(UseCaseBus $useCaseBus, EntityManager $entityManager)
    {
        parent::__construct($entityManager);
        $this->useCaseBus = $useCaseBus;
        $this->setEntityName('wolf-events.participant');
    }

    public function create($data)
    {
        $participantData = [
            'event_id' => $data['event_id'],
            'ticket_id' => $data['ticket_id'],
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            "fields" => $data['fields'] ?? []
        ];

        $participant = $this->useCaseBus->execute('wolf-events.create_participant', $participantData);
        return $this->loadById($participant->id);

    }
}