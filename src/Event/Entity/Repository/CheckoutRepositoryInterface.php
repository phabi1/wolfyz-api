<?php

namespace App\Event\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface CheckoutRepositoryInterface extends EntityRepositoryInterface
{
    public function getTotalAmountForEvent($eventId);
}