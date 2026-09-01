<?php

namespace App\Event\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;

class CreateSessionForEventUseCase implements UseCaseInterface {

    private $sessionRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->sessionRepository = $entityManager->getRepository('wolf-events.session');
    }

    public function execute(array $params = []) {
        $event_id = $params['event_id'];
        $data = $params['data'];

        $sessionData = $data;
        $sessionData['event_id'] = $event_id;
        return $this->sessionRepository->insert($sessionData);
    }
}