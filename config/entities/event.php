<?php

use App\Core\Entity\Definition\Field;
use App\Core\Entity\Definition\Relation;
use App\Event\Model\CheckoutStatus;

return [
    'wolf-events.event' =>
        [
            'table' => 'wolf_events_event',
            'repository' => \App\Event\Entity\Repository\EventRepository::class,
            'fields' => [
                'id' => ['type' => Field::TYPE_INTEGER],
                'slug' => ['type' => Field::TYPE_STRING, 'unique' => true],
                'title' => ['type' => Field::TYPE_STRING, 'required' => true],
                'event_type' => ['type' => Field::TYPE_STRING],
                'event_start' => ['type' => Field::TYPE_DATETIME, 'nullable' => true],
                'event_end' => ['type' => Field::TYPE_DATETIME, 'nullable' => true],
                'registration_start' => ['type' => Field::TYPE_DATETIME, 'nullable' => true],
                'registration_end' => ['type' => Field::TYPE_DATETIME, 'nullable' => true],
                'participant_nb' => ['type' => Field::TYPE_INTEGER, 'readonly' => true],
                'participant_max' => ['type' => Field::TYPE_INTEGER, 'nullable' => true],
                "participant_fields" => ['type' => Field::TYPE_JSON, 'nullable' => true],
            ],
            'relations' => [
                'sessions' => ['type' => Relation::TYPE_ONE_TO_MANY, 'target_entity' => 'wolf-events.session', 'options' => ['join_field' => 'event_id']],
                'tickets' => ['type' => Relation::TYPE_ONE_TO_MANY, 'target_entity' => 'wolf-events.ticket', 'options' => ['join_field' => 'event_id']],
            ]
        ],
    'wolf-events.ticket' =>
        [
            'table' => 'wolf_events_ticket',
            'repository' => \App\Event\Entity\Repository\TicketRepository::class,
            'fields' => [
                'id' => ['type' => Field::TYPE_INTEGER],
                'event_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'title' => ['type' => Field::TYPE_STRING, 'required' => true],
                'amount' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'participant_nb' => ['type' => Field::TYPE_INTEGER],
                'participant_max' => ['type' => Field::TYPE_INTEGER, 'nullable' => true],
                'participant_fields' => ['type' => Field::TYPE_JSON, 'nullable' => true]
            ],
            'relations' => []
        ],
    'wolf-events.checkout' =>
        [
            'table' => 'wolf_events_checkout',
            'repository' => \App\Event\Entity\Repository\CheckoutRepository::class,
            'fields' => [
                'id' => ['type' => Field::TYPE_INTEGER],
                'event_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'amount' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'seller_firstname' => ['type' => Field::TYPE_STRING, 'required' => true],
                'seller_lastname' => ['type' => Field::TYPE_STRING, 'required' => true],
                'seller_email' => ['type' => Field::TYPE_STRING, 'required' => true],
                'status' => [
                    'type' => Field::TYPE_STRING,
                    'required' => true,
                    'enum' => [
                        CheckoutStatus::PENDING,
                        CheckoutStatus::PAID,
                        CheckoutStatus::CANCELLED
                    ]
                ],
                'payed_at' => ['type' => Field::TYPE_DATETIME, 'nullable' => true],
                'billing_payment_id' => ['type' => Field::TYPE_INTEGER, 'nullable' => true],
                'meta' => ['type' => Field::TYPE_JSON, 'nullable' => true]
            ],
            'relations' => []
        ],
    'wolf-events.session' =>
        [
            'table' => 'wolf_events_session',
            'repository' => \App\Event\Entity\Repository\SessionRepository::class,
            'fields' => [
                'id' => ['type' => Field::TYPE_INTEGER],
                'title' => ['type' => Field::TYPE_STRING, 'required' => true],
                'event_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'session_start' => ['type' => Field::TYPE_DATETIME, 'required' => true],
                'session_end' => ['type' => Field::TYPE_DATETIME, 'required' => true],
            ],
            'relations' => []
        ],
    'wolf-events.participant' =>
        [
            'table' => 'wolf_events_participant',
            'repository' => \App\Event\Entity\Repository\ParticipantRepository::class,
            'fields' => [
                'id' => ['type' => Field::TYPE_INTEGER],
                'event_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'ticket_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'checkout_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'firstname' => ['type' => Field::TYPE_STRING, 'required' => true],
                'lastname' => ['type' => Field::TYPE_STRING, 'required' => true],
                'fields' => ['type' => Field::TYPE_JSON, 'nullable' => true],
            ],
            'relations' => [
                'checkout' => ['type' => Relation::TYPE_ONE_TO_ONE, 'target_entity' => 'wolf-events.checkout', 'options' => ['join_field' => 'checkout_id']]
            ]
        ],
    'wolf-events.registration' =>
        [
            'table' => 'wolf_events_registration',
            'repository' => \App\Event\Entity\Repository\RegistrationRepository::class,
            'fields' => [
                'id' => ['type' => Field::TYPE_INTEGER],
                'event_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'session_id' => ['type' => Field::TYPE_INTEGER, 'nullable' => true],
                'participant_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
                'status' => ['type' => Field::TYPE_STRING, 'required' => true, 'enum' => ['yes', 'no', 'unknown']],
            ],
            'relations' => []
        ],
];