<?php

use App\Core\Entity\Definition\Field;
use App\Core\Entity\Definition\Relation;

return [
    'wolf-memberships.campaign' => [
        'repository' => App\Membership\Entity\Repository\CampaignEntityRepository::class,
        'table' => 'wolf_memberships_campaign',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'title' => ['type' => Field::TYPE_STRING],
            'description' => ['type' => Field::TYPE_STRING],
            'start_date' => ['type' => Field::TYPE_DATE],
            'end_date' => ['type' => Field::TYPE_DATE],
            'registration_start' => ['type' => Field::TYPE_DATE],
            'registration_end' => ['type' => Field::TYPE_DATE],
            'participant_fields' => ['type' => Field::TYPE_JSON, 'nullable' => true],
            'settings' => ['type' => Field::TYPE_JSON, 'readonly' => true]
        ],
        'relations' => []
    ],
    'wolf-memberships.period' => [
        'table' => 'wolf_memberships_period',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'title' => ['type' => Field::TYPE_STRING],
            'start_date' => ['type' => Field::TYPE_DATE],
            'end_date' => ['type' => Field::TYPE_DATE],
            'campaign_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
        ],
        'relations' => [
            'campaign' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.campaign',
                'options' => [
                    'join_field' => 'campaign_id'
                ]
            ]
        ]
    ],
    'wolf-memberships.lesson' => [
        'table' => 'wolf_memberships_lesson',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'campaign_id' => ['type' => Field::TYPE_INTEGER],
            'title' => ['type' => Field::TYPE_STRING],
            'description' => ['type' => Field::TYPE_STRING],
            'day' => ['type' => Field::TYPE_INTEGER],
            'lesson_start' => ['type' => Field::TYPE_DATETIME],
            'lesson_end' => ['type' => Field::TYPE_DATETIME],
            'age_min' => ['type' => Field::TYPE_INTEGER, 'nullable' => true],
            'age_max' => ['type' => Field::TYPE_INTEGER, 'nullable' => true],
            'participant_nb' => ['type' => Field::TYPE_INTEGER, 'readonly' => true],
            'participant_max' => ['type' => Field::TYPE_INTEGER, 'nullable' => true],

        ],
        'relations' => [
            'campaign' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.campaign',
                'options' => [
                    'join_field' => 'campaign_id'
                ]
            ]
        ]
    ],
    'wolf-memberships.member' => [
        'repository' => 'member',
        'table' => 'wolf_memberships_member',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'firstname' => ['type' => Field::TYPE_STRING, 'required' => true],
            'lastname' => ['type' => Field::TYPE_STRING, 'required' => true],
            'birthdate' => ['type' => Field::TYPE_DATE, 'required' => true],
            'phone' => ['type' => Field::TYPE_PHONE, 'nullable' => true],
            'email' => ['type' => Field::TYPE_EMAIL, 'nullable' => true],
            'gender' => ['type' => Field::TYPE_STRING, 'nullable' => true],
            'address' => ['type' => Field::TYPE_JSON],
            'license_number' => ['type' => Field::TYPE_STRING],
            'avatar_url' => ['type' => Field::TYPE_STRING, 'nullable' => true],
            'hash' => ['type' => Field::TYPE_STRING, 'readonly' => true, 'hidden' => true]
        ],
        'relations' => [
            'subscriptions' => [
                'type' => Relation::TYPE_ONE_TO_MANY,
                'target_entity' => 'wolf-memberships.subscription',
                'options' => [
                    'join_field' => 'member_id'
                ]
            ],
            'wheel_assignments' => [
                'type' => Relation::TYPE_ONE_TO_MANY,
                'target_entity' => 'wolf-memberships.member_wheel_assignment',
                'options' => [
                    'join_field' => 'member_id'
                ]
            ]
        ]
    ],
    'wolf-memberships.request' => [
        'table' => 'wolf_memberships_request',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'status' => ['type' => Field::TYPE_STRING, 'required' => true],
            'firstname' => ['type' => Field::TYPE_STRING, 'required' => true],
            'lastname' => ['type' => Field::TYPE_STRING, 'required' => true],
            'email' => ['type' => Field::TYPE_EMAIL, 'required' => true],
            'phone' => ['type' => Field::TYPE_PHONE, 'nullable' => true],
            'data' => ['type' => Field::TYPE_JSON, 'nullable' => true],
            'discount_amount' => ['type' => Field::TYPE_INTEGER],
            'token' => ['type' => Field::TYPE_STRING, 'required' => true, 'exclude' => true],
            'campaign_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
        ],
    ],
    'wolf-memberships.request_log' => [
        'table' => 'wolf_memberships_request_log',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'request_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
            'status' => ['type' => Field::TYPE_STRING, 'required' => true],
            'params' => ['type' => Field::TYPE_JSON, 'nullable' => true],
            'changed_at' => ['type' => Field::TYPE_DATETIME, 'required' => true],
            'changed_by' => ['type' => Field::TYPE_STRING, 'nullable' => true],
            'created_at' => ['type' => Field::TYPE_DATETIME, 'required' => true],
        ],
        'relations' => [
            'request' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.request',
                'options' => [
                    'join_field' => 'request_id'
                ]
            ]
        ]
    ],
    'wolf-memberships.subscription' => [
        'table' => 'wolf_memberships_subscription',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'license_type' => ['type' => Field::TYPE_STRING, 'required' => true],
            'license_taken_at' => ['type' => Field::TYPE_DATETIME, 'nullable' => true],
            'fields' => ['type' => Field::TYPE_JSON, 'nullable' => true],
            'subscribed_at' => ['type' => Field::TYPE_DATETIME, 'required' => true],
            'campaign_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
            'member_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
        ],
        'relations' => [
            'campaign' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.campaign',
                'options' => [
                    'join_field' => 'campaign_id'
                ]
            ],
            'member' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.member',
                'options' => [
                    'join_field' => 'member_id'
                ]
            ],
            "contacts" => [
                'type' => Relation::TYPE_ONE_TO_MANY,
                'target_entity' => 'wolf-memberships.contact',
                'options' => [
                    'join_field' => 'subscription_id'
                ]
            ],
            "sessions" => [
                'type' => Relation::TYPE_ONE_TO_MANY,
                'target_entity' => 'wolf-memberships.session',
                'options' => [
                    'join_field' => 'subscription_id'
                ]
            ]
        ],
        'search' => ['member.lastname', 'member.firstname']
    ],
    'wolf-memberships.contact' => [
        'table' => 'wolf_memberships_contact',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'firstname' => ['type' => Field::TYPE_STRING, 'required' => true],
            'lastname' => ['type' => Field::TYPE_STRING, 'required' => true],
            'phone' => ['type' => Field::TYPE_STRING, 'nullable' => true],
            'email' => ['type' => Field::TYPE_STRING, 'nullable' => true],
            'owner' => ['type' => Field::TYPE_BOOLEAN, 'nullable' => true],
            "subscription_id" => ['type' => Field::TYPE_INTEGER, 'required' => true],
        ],
        'relations' => [
            'subscription' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.subscription',
                'options' => [
                    'join_field' => 'subscription_id'
                ]
            ]
        ]
    ],
    'wolf-memberships.session' => [
        'repository' => App\Membership\Entity\Repository\SessionEntityRepository::class,
        'table' => 'wolf_memberships_session',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'campaign_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
            'subscription_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
            'member_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
            'lesson_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
        ],
        'relations' => [
            'campaign' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.campaign',
                'options' => [
                    'join_field' => 'campaign_id'
                ]
            ],
            'subscription' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.subscription',
                'options' => [
                    'join_field' => 'subscription_id'
                ]
            ],
            'lesson' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.lesson',
                'options' => [
                    'join_field' => 'lesson_id'
                ]
            ],
            'member' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.member',
                'options' => [
                    'join_field' => 'member_id'
                ]
            ]
        ]
    ],
    'wolf-memberships.wheel' => [
        'table' => 'wolf_memberships_wheel',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'title' => ['type' => Field::TYPE_STRING, 'required' => true],
            'color' => ['type' => Field::TYPE_STRING, 'required' => true],
            'parent_id' => ['type' => Field::TYPE_INTEGER, 'nullable' => true],
            'parent_path' => ['type' => Field::TYPE_STRING, 'nullable' => true, 'readonly' => true, 'hidden' => true],
        ]
    ],
    'wolf-memberships.member_wheel_assignment' => [
        'table' => 'wolf_memberships_wheel_assignment',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'member_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
            'wheel_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
            'assigned_at' => ['type' => Field::TYPE_DATE]
        ],
        'relations' => [
            'member' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.member',
                'options' => [
                    'join_field' => 'member_id'
                ]
            ],
            'wheel' => [
                'type' => Relation::TYPE_ONE_TO_ONE,
                'target_entity' => 'wolf-memberships.wheel',
                'options' => [
                    'join_field' => 'wheel_id'
                ]
            ]
        ]
    ]
];
