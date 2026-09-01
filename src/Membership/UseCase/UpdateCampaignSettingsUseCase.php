<?php

namespace App\Membership\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;
use App\Membership\Entity\Repository\CampaignEntityRepositoryInterface;

class UpdateCampaignSettingsUseCase implements UseCaseInterface
{
    protected CampaignEntityRepositoryInterface $campaignRepository;

    public function __construct(EntityManager $entityManager)
    {
        $campaignRepository = $entityManager->getRepository('wolf-memberships.campaign');
        if ($campaignRepository instanceof CampaignEntityRepositoryInterface === false) {
            throw new \RuntimeException('Campaign repository must implement CampaignEntityRepositoryInterface.');
        }
        $this->campaignRepository = $campaignRepository;
    }

    public function execute(array $params = [])
    {
        $campaignId = $params['campaign_id'] ?? null;
        if (!$campaignId) {
            throw new \InvalidArgumentException('Campaign ID is required.');
        }
        
        $settings = $params['settings'] ?? [];
        if (empty($settings)) {
            throw new \InvalidArgumentException('Settings are required.');
        }

        $this->campaignRepository->updateSettings($campaignId, $settings);
    }
}