<?php

namespace App\Event\Entity\Repository;

use App\Core\Entity\EntityRepository;

class CheckoutRepository extends EntityRepository implements CheckoutRepositoryInterface
{
    use EventRepositoryTrait;

    public function findByMeta($metaKey, $metaValue)
    {
        $where = 'json_extract(meta, \'$.' . $metaKey . '\') = \'' . $metaValue . '\'';

        $sql = $this->db->createQuery();
        $sql->from($this->definition['table'])
            ->where($where);
        return $this->db->row($sql);
    }

    public function getTotalAmountForEvent($eventId)
    {
        $sql = $this->db->createQuery();
        $sql->select('SUM(amount) as total')
            ->from($this->definition['table'])
            ->where($this->db->expr()->eq('event_id', $eventId));
        $result = $this->db->value($sql);
        return $result ? floatval($result) : 0;
    }
}