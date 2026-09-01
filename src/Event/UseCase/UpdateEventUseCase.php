<?php

namespace App\Event\UseCase;

use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Event\Entity\Repository\TicketRepositoryInterface;

class UpdateEventUseCase implements UseCaseInterface
{
    private $eventRepository;

    private TicketRepositoryInterface $ticketRepository;

    private $sessionRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->eventRepository = $entityManager->getRepository('wolf-events.event');
        $this->ticketRepository = $entityManager->getRepository('wolf-events.ticket');
        $this->sessionRepository = $entityManager->getRepository('wolf-events.session');
    }

    public function execute(array $params = [])
    {
        $oldEvent = $this->eventRepository->find($params['id']);

        $newEvent = [];

        foreach ($params as $key => $value) {
            if ($key === 'id' && $key === 'sessions' && $key === 'tickets') {
                continue;
            }
            if (isset($oldEvent->$key) && $oldEvent->$key != $value) {
                $newEvent[$key] = $value;
            }
        }

        if (!empty($newEvent)) {
            $this->eventRepository->update($oldEvent->id, $newEvent);
        }

        if (isset($params['sessions']) && !empty($params['sessions'])) {
            $this->updateSessions($params['sessions'], $oldEvent->sessions, $oldEvent->id);
        }

        if (isset($params['tickets']) && !empty($params['tickets'])) {
            $this->updateTickets($params['tickets'], $oldEvent->tickets, $oldEvent->id);
        }

        return $oldEvent->id;
    }

    private function updateSessions($newSessions, $oldSessions, $eventId)
    {
        foreach ($newSessions as $session) {
            if (isset($session['id'])) {
                $oldSession = $this->sessionRepository->find($session['id']);
                $newSessionData = [];

                foreach ($session as $key => $value) {
                    if ($key === 'id') {
                        continue;
                    }
                    if (isset($oldSession->$key) && $oldSession->$key != $value) {
                        $newSessionData[$key] = $value;
                    }
                }

                if (!empty($newSessionData)) {
                    $this->sessionRepository->update($session['id'], $newSessionData);
                }
            } else {
                $this->sessionRepository->insert([
                    'title' => $session['title'],
                    'session_start' => $session['session_start'],
                    'session_end' => $session['session_end'],
                    'event_id' => $eventId
                ]);
            }
        }

        // Remove sessions that are not in the new sessions list
        foreach ($oldSessions as $oldSession) {
            if (!in_array($oldSession->id, array_column($newSessions, 'id'))) {
                $this->sessionRepository->delete($oldSession->id);
            }
        }
    }

    private function updateTickets($newTickets, $oldTickets, $eventId)
    {
        foreach ($newTickets as $ticket) {
            if (isset($ticket['id'])) {
                $oldTicket = $this->ticketRepository->find($ticket['id']);
                $newTicketData = [];

                foreach ($ticket as $key => $value) {
                    if ($key === 'id') {
                        continue;
                    }
                    if (isset($oldTicket->$key) && $oldTicket->$key != $value) {
                        $newTicketData[$key] = $value;
                    }
                }

                if (!empty($newTicketData)) {
                    $this->ticketRepository->update($ticket['id'], $newTicketData);
                }
            } else {
                $this->ticketRepository->insert([
                    'title' => $ticket['title'],
                    'amount' => $ticket['amount'],
                    'participant_fields' => $ticket['participant_fields'] ?? [],
                    'event_id' => $eventId
                ]);
            }
        }

        // Remove tickets that are not in the new tickets list
        foreach ($oldTickets as $oldTicket) {
            if (!in_array($oldTicket->id, array_column($newTickets, 'id'))) {
                $this->ticketRepository->delete($oldTicket->id);
            }
        }
    }
}