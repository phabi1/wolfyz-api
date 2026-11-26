<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface SubscriptionEntityRepositoryInterface extends EntityRepositoryInterface
{
    public function countByGender($campaignId): array;
    public function countByLicenseType($campaignId, array $licenseTypes): array;
}