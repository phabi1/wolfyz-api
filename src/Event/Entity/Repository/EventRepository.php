<?php

namespace App\Event\Entity\Repository;

use App\Core\Entity\EntityRepository;

class EventRepository extends EntityRepository implements EventRepositoryInterface
{
    public function updateParticipantCount($eventId)
    {
        $sql = $this->db->createQuery();
        $sql->select('COUNT(*)')
            ->from("wolf_events_participant")
            ->where($this->db->expr()->eq('event_id', $eventId));

        $count = (int) $this->db->value($sql);

        $this->db->update("wolf_events_event", ['participant_nb' => $count], ['id' => $eventId]);
    }
}