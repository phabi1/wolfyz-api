<?php

namespace App\Billing\Entity\Repository;

use App\Core\Entity\EntityRepository;

class PaymentRepository extends EntityRepository implements PaymentRepositoryInterface
{
    public function amount(): int
    {
        $qb = $this->db->createQuery();
        $qb->from($this->definition->getTable());
        $qb->select('SUM(amount)', 'total');
        $result = $this->db->value($qb);

        return $result ?? 0;
    }
}