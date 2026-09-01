<?php

namespace App\Event\UseCase;

use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Event\Entity\Repository\ParticipantRespository;

class UpdateParticipantUseCase implements UseCaseInterface
{
    private ParticipantRespository $participantRepository;

    public function __construct(
        EntityManager $entityManager,
    ) {
        $participantRepository = $entityManager->getRepository('wolf-events.participant');
        if (!($participantRepository instanceof ParticipantRespository)) {
            throw new \Exception("Participant repository must be instance of ParticipantRespository");
        }
        $this->participantRepository = $participantRepository;
    }

    public function execute(array $params = [])
    {
        $participant = $this->participantRepository->find($params['id']);
        if (!$participant) {
            throw new \Exception('Participant not found');
        }

        $data = [];

        if (isset($params['firstname'])) {
            $data['firstname'] = $params['firstname'];
        }
        if (isset($params['lastname'])) {
            $data['lastname'] = $params['lastname'];
        }
        if (isset($params['fields'])) {
            $data['fields'] = $params['fields'];
        }

        $participant = $this->participantRepository->update($params['id'], $data);

        return $participant;
    }
}