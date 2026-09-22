<?php

namespace App\Event\UseCase\Registration;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseBus;
use App\Core\UseCase\UseCaseInterface;

class CheckSubscriptionUseCase implements UseCaseInterface
{
    private $useCaseBus;


    public function __construct(UseCaseBus $useCaseBus)
    {
        $this->useCaseBus = $useCaseBus;
    }

    public function execute(array $data = [])
    {
        $res = $this->useCaseBus->execute('wolf-memberships.exists_member', [
            'firstname' => $data['firstname'] ?? null,
            'lastname' => $data['lastname'] ?? null,
            'birthdate' => $data['birthdate'] ?? null
        ]);

        if ($res['exists'] === false) {
            return [
                'valid' => false,
                'errors' => ['member.required']
            ];
        }

        $currentCampaign = $this->useCaseBus->execute('wolf-memberships.get_current_campaign', []);

        if (empty($currentCampaign)) {
            return [
                'valid' => false,
                'errors' => ['current_campaign.required']
            ];
        }

        $hasSubscription = $this->useCaseBus->execute('wolf-memberships.has_subscription', [
            'member_id' => $res['id'],
            'campaign_id' => $currentCampaign->id
        ]);

        if (empty($hasSubscription)) {
            return [
                'valid' => false,
                'errors' => ['subscription.required']
            ];
        }

        return [
            'valid' => true,
            'errors' => []
        ];
        
    }
}