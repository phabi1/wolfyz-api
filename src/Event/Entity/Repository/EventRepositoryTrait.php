<?php

namespace App\Event\Entity\Repository;

trait EventRepositoryTrait
{
    public function findByEventId($eventId)
    {
        $sql = $this->db->createQuery()
            ->from($this->definition['table'])
            ->where(
                $this->db->expr()->eq('event_id', $eventId)
            );

        $res = $this->db->rows($sql);
        return array_map(function ($row) {
            return $this->unserialize($row);
        }, $res);
    }
}