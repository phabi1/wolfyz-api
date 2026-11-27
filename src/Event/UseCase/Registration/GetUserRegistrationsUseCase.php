<?php

namespace App\Event\UseCase\Registration;

use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;
use App\Event\Entity\Repository\CheckoutRepositoryInterface;

/**
 * Event registrations of a signed-in user ("My registrations" block of the
 * website): those made while signed in, or with their verified email.
 * Only what the user needs to see: no payment or participant form data.
 */
class GetUserRegistrationsUseCase implements UseCaseInterface
{
    private CheckoutRepositoryInterface $checkoutRepository;

    public function __construct(private readonly EntityManager $entityManager)
    {
        $checkoutRepository = $entityManager->getRepository('wolf-events.checkout');
        if (!$checkoutRepository instanceof CheckoutRepositoryInterface) {
            throw new \RuntimeException('Checkout repository must implement CheckoutRepositoryInterface');
        }
        $this->checkoutRepository = $checkoutRepository;
    }

    /**
     * @param array{user_id: string, email?: string|null} $params
     */
    public function execute(array $params = [])
    {
        $checkouts = $this->checkoutRepository->findForUser((string) $params['user_id'], $params['email'] ?? null);
        if ($checkouts === []) {
            return ['items' => []];
        }

        $events = $this->byId($this->entityManager->getRepository('wolf-events.event')
            ->findByIds(array_values(array_unique(array_map(fn ($c) => $c->event_id, $checkouts)))));
        $participants = $this->entityManager->getRepository('wolf-events.participant')
            ->find(['checkout_id' => ['in' => array_map(fn ($c) => $c->id, $checkouts)]]);
        $tickets = $participants === [] ? [] : $this->byId($this->entityManager->getRepository('wolf-events.ticket')
            ->findByIds(array_values(array_unique(array_map(fn ($p) => $p->ticket_id, $participants)))));

        $participantsByCheckout = [];
        foreach ($participants as $participant) {
            $participantsByCheckout[$participant->checkout_id][] = [
                'firstname' => $participant->firstname,
                'lastname' => $participant->lastname,
                'ticket' => $tickets[$participant->ticket_id]->title ?? null,
            ];
        }

        $items = [];
        foreach ($checkouts as $checkout) {
            $event = $events[$checkout->event_id] ?? null;
            $items[] = [
                'id' => $checkout->id,
                'status' => $checkout->status,
                'amount' => $checkout->amount,
                'payed_at' => $checkout->payed_at ?? null,
                'event' => $event ? [
                    'id' => $event->id,
                    'title' => $event->title,
                    'event_start' => $event->event_start ?? null,
                    'event_end' => $event->event_end ?? null,
                ] : null,
                'participants' => $participantsByCheckout[$checkout->id] ?? [],
            ];
        }

        return ['items' => $items];
    }

    /** @return array<int, \stdClass> */
    private function byId(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = $row;
        }

        return $result;
    }
}
