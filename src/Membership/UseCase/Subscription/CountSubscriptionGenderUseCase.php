<?php

namespace App\Membership\UseCase\Subscription;

use App\Membership\Entity\Repository\SubscriptionEntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;

class CountSubscriptionGenderUseCase implements UseCaseInterface
{
    private SubscriptionEntityRepositoryInterface $subscriptionRepository;

    public function __construct(EntityManager $entityManager)
    {
        $subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
        if (!$subscriptionRepository instanceof SubscriptionEntityRepositoryInterface) {
            throw new \RuntimeException('Subscription repository must be an instance of SubscriptionEntityRepositoryInterface');
        }
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function execute(array $params = []): array
    {
        $campaignId = $params['campaign_id'];
        $status = ['pending', 'approved', 'rejected', 'cancelled', 'paid'];
        $countByStatus = $this->subscriptionRepository->countByStatus($campaignId, $status);

        return array_reduce($status, function ($carry, $s) use ($countByStatus) {
            $carry[$s] = isset($countByStatus[$s]) ? $countByStatus[$s] : 0;
            return $carry;
        }, []);
    }
}