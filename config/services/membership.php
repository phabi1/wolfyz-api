<?php
return [
    'wolf-memberships.controller.dashboard' => [
        'class' => \App\Membership\Controller\DashboardController::class,
        'arguments' => [
            '@wolf-memberships.dashboard.source_bus'
        ]
    ],
    'wolf-memberships.controller.campaign' => [
        'class' => \App\Membership\Controller\CampaignController::class
    ],
    'wolf-memberships.controller.member' => [
        'class' => \App\Membership\Controller\MemberController::class,
        'arguments' => [
            '@use_case_bus',
            '@wolf-memberships.entity.service.member'
        ]
    ],
    'wolf-memberships.controller.subscription' => [
        'class' => \App\Membership\Controller\SubscriptionController::class,
        'arguments' => [
            '@use_case_bus'
        ]
    ],
    'wolf-memberships.controller.session' => [
        'class' => \App\Membership\Controller\SessionController::class
    ],
    'wolf-memberships.controller.lesson' => [
        'class' => \App\Membership\Controller\LessonController::class,
        'arguments' => [
            '@use_case_bus'
        ]
    ],
    'wolf-memberships.controller.period' => [
        'class' => \App\Membership\Controller\PeriodController::class,
        'arguments' => [
            '@use_case_bus'
        ]
    ],
    'wolf-memberships.controller.registration' => [
        'class' => \App\Membership\Controller\RegistrationController::class,
        'arguments' => [
            '@use_case_bus'
        ]
    ],
    'wolf-memberships.controller.contact' => [
        'class' => \App\Membership\Controller\ContactController::class
    ],
    'wolf-memberships.controller.wheel' => [
        'class' => \App\Membership\Controller\WheelController::class
    ],
    'wolf-memberships.controller.wheel_assignment' => [
        'class' => \App\Membership\Controller\WheelAssignmentController::class
    ],
    'wolf-memberships.controller.request' => [
        'class' => \App\Membership\Controller\RequestController::class
    ],
    'wolf-memberships.controller.file' => [
        'class' => \App\Membership\Controller\FileController::class
    ],
    'wolf-memberships.dashboard.source_bus' => [
        'class' => \App\Membership\Dashboard\SourceBus::class
    ],
    'wolf-memberships.entity.service.member' => [
        'class' => \App\Membership\Entity\Service\MemberEntityService::class,
        'arguments' => [
            '@entity.manager',
            '@wolf-memberships.helper.member'
        ]
    ],
    'wolf-memberships.helper.member' => [
        'class' => \App\Membership\Helper\MemberHelper::class,
        'arguments' => [
            '@helper.string'
        ]
    ],
    'wolf-memberships.entity.service.subscription' => [
        'factory' => [\App\Membership\Entity\Service\SubscriptionEntityServiceFactory::class, 'create'],
        'arguments' => [
            '@entity.manager',
        ]
    ],
    'wolf-memberships.dashboard.source.lessons_completude' => [
        'class' => \App\Membership\Dashboard\Source\LessonsCompletude::class,
        'arguments' => [
            '@use_case_bus'
        ],
        'tags' => [
            [
                'name' => 'wolf-memberships.dashboard.source',
                'value' => 'lessons_completude'
            ]
        ]
    ],
    'wolf-memberships.dashboard.source.get_periods_for_print' => [
        'class' => \App\Membership\Dashboard\Source\GetPeriodsForPrint::class,
        'arguments' => [
            '@entity.manager'
        ],
        'tags' => [
            [
                'name' => 'wolf-memberships.dashboard.source',
                'value' => 'get_periods_for_print'
            ]
        ]
    ],
    'wolf-memberships.use_case.get_lessons_completude' => [
        'class' => \App\Membership\UseCase\GetLessonsCompletudeUseCase::class,
        'arguments' => [
            '@db',
            '@helper.date'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.get_lessons_completude'
            ]
        ]
    ],
    'wolf-memberships.use_case.import_members' => [
        'class' => \App\Membership\UseCase\ImportMembersUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@wolf-memberships.helper.member'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.import_members'
            ]
        ]
    ],
    'wolf-memberships.use_case.import_subscriptions' => [
        'class' => \App\Membership\UseCase\ImportSubscriptionsUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@wolf-memberships.helper.member',
            '@helper.date'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.import_subscriptions'
            ]
        ]
    ],
    'wolf-memberships.use_case.export_subscriptions' => [
        'class' => \App\Membership\UseCase\ExportSubscriptionsUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@wolf-memberships.helper.member'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.export_subscriptions'
            ]
        ]
    ],
    'wolf-memberships.use_case.print_period' => [
        'class' => \App\Membership\UseCase\PrintPeriodUseCase::class,
        'arguments' => [
            '@entity.manager'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.print_period'
            ]
        ]
    ],
    'wolf-memberships.use_case.exists_member' => [
        'class' => \App\Membership\UseCase\ExistsMemberUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@wolf-memberships.helper.member'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.exists_member'
            ]
        ]
    ],
    'wolf-memberships.use_case.get_registration_for_campaign' => [
        'class' => \App\Membership\UseCase\GetRegistrationUseCase::class,
        'arguments' => [
            '@entity.manager'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.get_registration_for_campaign'
            ]
        ]
    ],
    'wolf-memberships.use_case.register_to_campaign' => [
        'class' => \App\Membership\UseCase\RegisterToCampaignUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@mail'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.register_to_campaign'
            ]
        ]
    ],
    'wolf-memberships.use_case.update_request' => [
        'class' => \App\Membership\UseCase\UpdateRequestUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@mail'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.update_request'
            ]
        ]
    ],
    'wolf-memberships.use_case.calculate_registration_total' => [
        'class' => \App\Membership\UseCase\CalculateRegistrationTotalUseCase::class,
        'arguments' => [
            '@entity.manager',
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.calculate_registration_total'
            ]
        ]
    ],
    'wolf-memberships.use_case.pay' => [
        'class' => \App\Membership\UseCase\PayUseCase::class,
        'arguments' => [
            '@use_case_bus'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.pay'
            ]
        ]
    ],
    'wolf-memberships.use_case.mark_as_approved_request' => [
        'class' => \App\Membership\UseCase\MarkAsApprovedRequestUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@mail'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.approve_request'
            ]
        ]
    ],
    'wolf-memberships.use_case.mark_as_rejected_request' => [
        'class' => \App\Membership\UseCase\MarkAsRejectedRequestUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@mail'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.reject_request'
            ]
        ]
    ],
    'wolf-memberships.use_case.mark_as_paid_request' => [
        'class' => \App\Membership\UseCase\MarkAsPaidRequestUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@mail'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.mark_as_paid_request'
            ]
        ]
    ],
    'wolf-memberships.use_case.mark_as_cancelled_request' => [
        'class' => \App\Membership\UseCase\MarkAsCancelledRequestUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@mail'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.cancel_request'
            ]
        ]
    ],
    'wolf-memberships.use_case.get_history_of_request' => [
        'class' => \App\Membership\UseCase\GetHistoryOfRequestUseCase::class,
        'arguments' => [
            '@entity.manager'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.get_history_of_request'
            ]
        ]
    ],
    'wolf-memberships.use_case.convert_request_to_subscriptions' => [
        'class' => \App\Membership\UseCase\ConvertRequestToSubscriptionsUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@wolf-memberships.helper.member',
            '@helper.date'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.convert_request_to_subscriptions'
            ]
        ]
    ],
    'wolf-memberships.use_case.update_campaign_settings' => [
        'class' => \App\Membership\UseCase\UpdateCampaignSettingsUseCase::class,
        'arguments' => [
            '@entity.manager'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.update_campaign_settings'
            ]
        ]
    ],
    'wolf-memberships.use_case.resend_payment' => [
        'class' => \App\Membership\UseCase\ResendPaymentUseCase::class,
        'arguments' => [
            '@entity.manager',
            '@mail'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.resend_payment'
            ]
        ]
    ],
    'wolf-memberships.use_case.current_wheels' => [
        'class' => \App\Membership\UseCase\CurrentWheelsUseCase::class,
        'arguments' => [
            '@db'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.current_wheels'
            ]
        ]
    ],
    'wolf-memberships.use_case.next_wheels' => [
        'class' => \App\Membership\UseCase\NextWheelsUseCase::class,
        'arguments' => [
            '@db'
        ],
        'tags' => [
            [
                'name' => 'use_case',
                'value' => 'wolf-memberships.next_wheels'
            ]
        ]
    ],
];