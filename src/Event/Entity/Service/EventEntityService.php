<?php

namespace App\Event\Entity\Service;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityService;
use App\Core\UseCase\UseCaseBus;

class EventEntityService extends EntityService
{
    private UseCaseBus $useCaseBus;


    public function __construct(UseCaseBus $useCaseBus, EntityManager $entityManager)
    {
        parent::__construct($entityManager);
        $this->useCaseBus = $useCaseBus;
        $this->setEntityName('wolf-events.event');
    }

    public function create($data)
    {
        $eventData = $data;

        $event = $this->useCaseBus->execute('wolf-events.create_event', $eventData);
        return $this->loadById($event->id);

    }

    public function update($id, $data)
    {
        $eventData = array_merge(['id' => $id], $data);
        $this->useCaseBus->execute('wolf-events.update_event', $eventData);
        return $this->loadById($id);
    }
}