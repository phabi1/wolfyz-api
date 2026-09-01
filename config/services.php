<?php

return [
    'parameters' => [
        'class' => \App\Core\Config\Parameters::class
    ],
    'db' => [
        'factory' => [\App\Core\Db\DbFactory::class, 'create'],
        'arguments' => [
            '@parameters'
        ]
    ],
    'settings' => [
        'class' => \App\Core\Config\Settings::class,
        'arguments' => [
            '@db',
            'wolf_settings'
        ]
    ],
    'mail' => [
        'class' => \App\Core\Mail\MailService::class
    ],
    'rest.routes' => [
        'class' => \App\Core\Rest\Routes::class
    ],
    'entity.definition' => [
        'factory' => [\App\Core\Entity\EntityDefinitionFactory::class, 'create']
    ],
    'entity.manager' => [
        'class' => \App\Core\Entity\EntityManager::class,
        'arguments' => [
            '@entity.definition',
            '@db'
        ]
    ],
    'use_case_bus' => [
        'class' => \App\Core\UseCase\UseCaseBus::class
    ],
    'helper.string' => [
        'class' => \App\Core\Helper\StringHelper::class
    ],
    'helper.date' => [
        'class' => \App\Core\Helper\DateHelper::class
    ],
];