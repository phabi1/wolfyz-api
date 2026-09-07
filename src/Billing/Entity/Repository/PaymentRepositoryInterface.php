<?php

namespace App\Billing\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface PaymentRepositoryInterface extends EntityRepositoryInterface
{
    public function amount(): int;
}