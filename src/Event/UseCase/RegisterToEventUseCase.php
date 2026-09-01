<?php

namespace App\Event\UseCase;

use App\Core\UseCase\UseCaseBus;
use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;
use App\Event\Entity\Repository\EventRepository;
use App\Event\Entity\Repository\TicketRepository;

class RegisterToEventUseCase implements UseCaseInterface
{
    private $participantRepository;

    private $checkoutRepository;

    private UseCaseBus $useCaseBus;

    private EventRepository $eventRepository;

    private TicketRepository $ticketRepository;

    public function __construct(EntityManager $entityManager, UseCaseBus $useCaseBus)
    {

        $this->participantRepository = $entityManager->getRepository('wolf-events.participant');
        $this->checkoutRepository = $entityManager->getRepository('wolf-events.checkout');
        $eventRepository = $entityManager->getRepository('wolf-events.event');
        if (!($eventRepository instanceof EventRepository)) {
            throw new \Exception("Event repository must be instance of EventRepository");
        }
        $this->eventRepository = $eventRepository;
        $ticketRepository = $entityManager->getRepository('wolf-events.ticket');
        if (!($ticketRepository instanceof TicketRepository)) {
            throw new \Exception("Ticket repository must be instance of TicketRepository");
        }
        $this->ticketRepository = $ticketRepository;

        $this->useCaseBus = $useCaseBus;
    }

    public function execute(array $params = [])
    {
        $event = $this->eventRepository->findById($params['event_id']);
        if (!$event) {
            throw new \Exception("Event not found");
        }

        $checkoutData = [
            'event_id' => $event->id,
            'seller_firstname' => $params['registration']['firstname'],
            'seller_lastname' => $params['registration']['lastname'],
            'seller_email' => $params['registration']['email'],
            'amount' => $params['registration']['amount'] ?? 0,
            'meta' => $params['registration']['meta'] ?? []
        ];

        $checkout = $this->checkoutRepository->insert($checkoutData);

        $ticketUpdates = [];

        foreach ($params['participants'] as $participant) {
            $data = [
                'firstname' => $participant['firstname'],
                'lastname' => $participant['lastname'],
                'fields' => $participant['fields'] ?? [],
                'event_id' => $event->id,
                'ticket_id' => $participant['ticket_id'] ?? null,
                'checkout_id' => $checkout->id
            ];

            $this->participantRepository->insert($data);
            if (isset($data['ticket_id'])) {
                if (!in_array($data['ticket_id'], $ticketUpdates)) {
                    $ticketUpdates[] = $data['ticket_id'];
                }
            }
        }

        $this->eventRepository->updateParticipantCount($event->id);

        foreach ($ticketUpdates as $ticketId) {
            $this->ticketRepository->updateParticipantCount($ticketId);
        }

        $paymentType = $params['payment_type'] ?? 'helloasso';

        $response = $this->useCaseBus->execute('wolf-billing.create_payment', [
            'amount' => $checkout->amount,
            'currency' => 'EUR',
            'payment_method' => $paymentType,
            'name' => 'Inscription à l\'événement ' . $event->title,
            'payer' => [
                'first_name' => $checkout->seller_firstname,
                'last_name' => $checkout->seller_lastname,
                'email' => $checkout->seller_email
            ],
            'metadata' => ['external_id' => 'event:' . $checkout->id]
        ]);

        return [
            'success' => true,
            'message' => 'Inscription réussie',
            'payment_url' => $response['redirect_url'] ?? null
        ];
    }
}