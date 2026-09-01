<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface CampaignEntityRepositoryInterface extends EntityRepositoryInterface
{

    function updateSettings(int $campaignId, array $settings);

}