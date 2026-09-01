<?php return [
    'wolf-events.controller.checkout' => [
        'class' => \App\Event\Controller\CheckoutController::class,
    ],
    'wolf-events.controller.event' => [
        'class' => \App\Event\Controller\EventController::class,
        'arguments' => [
            '@wolf-events.event.entity.service'
        ]
    ],
    'wolf-events.controller.participant' => [
        'class' => \App\Event\Controller\ParticipantController::class,
        'arguments' => [
            '@wolf-events.participant.entity.service'
        ]
    ],
    'wolf-events.controller.session' => [
        'class' => \App\Event\Controller\SessionController::class
    ],
    'wolf-events.controller.ticket' => [
        'class' => \App\Event\Controller\TicketController::class
    ],
    'wolf-events.controller.registration' => [
        'class' => \App\Event\Controller\RegistrationController::class
    ],
    'wolf-events.repository.event' => [
        'class' => \App\Event\Entity\Repository\EventRepository::class
    ],
    'wolf-events.repository.participant' => [
        'class' => \App\Event\Entity\Repository\ParticipantRepository::class
    ],
    'wolf-events.event.entity.service' => [
        'class' => \App\Event\Entity\Service\EventEntityService::class,
        'arguments' => ['@use_case_bus', '@entity.manager']
    ],
    'wolf-events.participant.entity.service' => [
        'class' => \App\Event\Entity\Service\ParticipantEntityService::class,
        'arguments' => ['@use_case_bus', '@entity.manager']
    ],
    'wolf-events.token.service' => [
        'class' => \App\Event\Token\TokenService::class,
    ],
    'wolf-events.use_case.get_event' => [
        'class' => \App\Event\UseCase\GetEventUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.get_event']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.create_event' => [
        'class' => \App\Event\UseCase\CreateEventUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.create_event']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.update_event' => [
        'class' => \App\Event\UseCase\UpdateEventUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.update_event']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.create_session_for_event' => [
        'class' => \App\Event\UseCase\CreateSessionForEventUseCase::class,
        'arguments' => [
            '@entity.manager'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-events.create_session_for_event'
            ]
        ]
    ],
    'wolf-events.use_case.create_ticket_for_event' => [
        'class' => \App\Event\UseCase\CreateTicketForEventUseCase::class,
        'arguments' => [
            '@entity.manager'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-events.create_ticket_for_event'
            ]
        ]
    ],
    'wolf-events.use_case.register_to_event' => [
        'class' => \App\Event\UseCase\RegisterToEventUseCase::class,
        'arguments' => ['@entity.manager', '@use_case_bus'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.register_to_event']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.paid_checkout' => [
        'class' => \App\Event\UseCase\PaidCheckoutUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.paid_checkout']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.get_checkout_result' => [
        'class' => \App\Event\UseCase\GetCheckoutResultUseCase::class,
        'arguments' => ['@entity.manager', '@wolf-events.token.service'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.get_checkout_result']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.print_participants' => [
        'class' => \App\Event\UseCase\PrintParticipantsUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.print_participants']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.create_participant' => [
        'class' => \App\Event\UseCase\CreateParticipantUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.create_participant']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.update_participant' => [
        'class' => \App\Event\UseCase\UpdateParticipantUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.update_participant']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.delete_participant' => [
        'class' => \App\Event\UseCase\DeleteParticipantUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.delete_participant']
        ],
        'shared' => false
    ],
    'wolf-events.use_case.get_amount_for_event' => [
        'class' => \App\Event\UseCase\GetAmountForEventUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use_case', 'value' => 'wolf-events.get_amount_for_event']
        ],
        'shared' => false
    ]
];