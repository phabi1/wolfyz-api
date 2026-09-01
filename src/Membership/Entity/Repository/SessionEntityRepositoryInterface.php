<?php

namespace App\Membership\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface SessionEntityRepositoryInterface extends EntityRepositoryInterface
{
    public function countByLessons(array $lessonIds): array;
}