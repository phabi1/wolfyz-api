<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface RequestEntityRepositoryInterface extends EntityRepositoryInterface
{
    public function countByStatus($campaignId, array $status): array;
}