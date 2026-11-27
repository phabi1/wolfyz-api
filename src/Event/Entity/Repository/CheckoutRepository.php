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

    /**
     * Registrations of a user: made while signed in (user_id), or with their
     * verified email address (older registrations).
     *
     * @return \stdClass[]
     */
    public function findForUser(string $userId, ?string $verifiedEmail): array
    {
        $expr = $this->db->expr();
        $where = $expr->or([$expr->eq('user_id', $userId)]);
        if ($verifiedEmail !== null && $verifiedEmail !== '') {
            $where->add($expr->eq('seller_email', $verifiedEmail));
        }

        $sql = $this->db->createQuery();
        $sql->from($this->definition['table'])
            ->where($where)
            ->orderBy('id', 'desc');

        return array_map(fn ($row) => $this->unserialize($row), $this->db->rows($sql));
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