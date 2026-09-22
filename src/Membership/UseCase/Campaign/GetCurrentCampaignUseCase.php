<?php

namespace App\Membership\UseCase\Campaign;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;
use App\Core\UseCase\UseCaseBus;

class GetCurrentCampaignUseCase implements UseCaseInterface
{
    private EntityRepositoryInterface $campaignRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
    }

    public function execute(array $data = [])
    {
        $now = time();

        $date = '"' . date('Y-m-d', $now) . '"';

        return $this->campaignRepository->findOne([
            'start_date' => ['lte' => $date],
            'end_date' => ['gte' => $date],
        ]);
    }
}