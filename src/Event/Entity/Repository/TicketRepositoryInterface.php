<?php

namespace App\Event\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface TicketRepositoryInterface extends EntityRepositoryInterface, EventAwareInterface
{
    public function updateParticipantCount($ticketId);
}