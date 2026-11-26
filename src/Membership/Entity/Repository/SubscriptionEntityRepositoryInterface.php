<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface SubscriptionEntityRepositoryInterface extends EntityRepositoryInterface
{
    public function countByGender($campaignId): array;
    public function countByLicenseType($campaignId, array $licenseTypes): array;

    /**
     * Counts the members of a campaign by age (in whole years) at the reference date.
     *
     * @return array{ages: array<int, int>, unknown: int}
     */
    public function countByAge($campaignId, \DateTimeInterface $referenceDate): array;
}