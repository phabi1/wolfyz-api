<?php

namespace App\Event\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface EventRepositoryInterface extends EntityRepositoryInterface
{
    public function updateParticipantCount($eventId);
}