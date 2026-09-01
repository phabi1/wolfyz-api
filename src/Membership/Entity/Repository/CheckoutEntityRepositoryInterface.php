<?php

namespace App\Membership\Entity\Repository;

use stdClass;
use App\Core\Entity\EntityRepositoryInterface;

interface CheckoutEntityRepositoryInterface extends EntityRepositoryInterface
{
    public function findByOrder(int $campaignId, string $orderId): stdClass|null;
}