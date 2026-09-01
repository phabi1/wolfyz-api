<?php

namespace App\Event\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface ParticipantRepositoryInterface extends EntityRepositoryInterface, EventAwareInterface
{
    public function deleteByCheckoutId($checkoutId);
}