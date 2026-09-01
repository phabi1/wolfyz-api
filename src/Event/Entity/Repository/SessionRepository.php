<?php

namespace App\Event\Entity\Repository;

use App\Core\Entity\EntityRepository;

class SessionRepository extends EntityRepository implements EventAwareInterface
{
    use EventRepositoryTrait;
}