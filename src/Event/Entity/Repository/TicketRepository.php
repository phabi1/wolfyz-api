<?php

namespace App\Event\Entity\Repository;

use App\Core\Entity\EntityRepository;

class TicketRepository extends EntityRepository implements TicketRepositoryInterface
{
    use EventRepositoryTrait;
    
    public function updateParticipantCount($ticketId)
    {
        $sql = $this->db->createQuery();
        $sql->select('COUNT(*)')
            ->from("wolf_events_participant")
            ->where($this->db->expr()->eq('ticket_id', $ticketId));

        $count = (int) $this->db->value($sql);

        $this->db->update("wolf_events_ticket", ['participant_nb' => $count], ['id' => $ticketId]);
    }
}