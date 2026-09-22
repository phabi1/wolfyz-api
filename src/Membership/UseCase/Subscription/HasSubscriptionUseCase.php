<?php

namespace App\Membership\UseCase\Subscription;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;

class HasSubscriptionUseCase implements UseCaseInterface
{
    private $subscriptionRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->subscriptionRepository = $entityManager->getRepository('wolf-memberships.subscription');
    }

    public function execute(array $data = [])
    {
        $memberId = $data['member_id'] ?? null;
        $campaignId = $data['campaign_id'] ?? null;

        if (empty($memberId) || empty($campaignId)) {
            return false;
        }

        $subscription = $this->subscriptionRepository->exists([
            'member_id' => ['eq' => $memberId],
            'campaign_id' => ['eq' => $campaignId]
        ]);

        return $subscription;
    }
}