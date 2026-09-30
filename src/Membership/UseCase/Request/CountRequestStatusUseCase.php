<?php

namespace App\Membership\UseCase\Request;

use App\Membership\Entity\Repository\RequestEntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;

class CountRequestStatusUseCase implements UseCaseInterface
{
    private RequestEntityRepositoryInterface $requestRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->requestRepository = $entityManager->getRepository('wolf-memberships.request');
    }

    public function execute(array $params = []): array
    {
        $campaignId = $params['campaign_id'];
        $status = ['pending', 'approved', 'rejected', 'cancelled', 'paid'];
        $countByStatus = $this->requestRepository->countByStatus($campaignId, $status);

        return array_reduce($status, function ($carry, $s) use ($countByStatus) {
            $carry[$s] = isset($countByStatus[$s]) ? $countByStatus[$s] : 0;
            return $carry;
        }, []);
    }
}