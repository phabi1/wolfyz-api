<?php

namespace App\Event\Entity\Repository;

interface EventAwareInterface
{
    public function findByEventId(int $eventId);
}