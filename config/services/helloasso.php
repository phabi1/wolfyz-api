<?php

return [
    'wolf-helloasso.sdk' => [
        'class' => \App\HelloAsso\Sdk\Client::class,
        'arguments' => [
            '!helloasso.credentials.api_key',
            '!helloasso.credentials.api_secret',
            '!helloasso.organization_slug',
            '!helloasso.options'
        ],
    ],
    'wolf-helloasso.payment.strategy.helloasso' => [
        'class' => \App\HelloAsso\Payment\Strategy\HelloAsso::class,
        'arguments' => ['@wolf-helloasso.sdk'],
        'tags' => [
            ['name' => 'wolf_billing.payment.strategy', 'value' => 'helloasso']
        ]
    ],
    'wolf-helloasso.payment.strategy.multiplehelloasso' => [
        'class' => \App\HelloAsso\Payment\Strategy\MultipleHelloAsso::class,
        'arguments' => ['@wolf-helloasso.sdk'],
        'tags' => [
            ['name' => 'wolf_billing.payment.strategy', 'value' => 'multiplehelloasso']
        ]
    ],
    'wolf-helloasso.controller.webhook' => [
        'class' => \App\HelloAsso\Controller\WebhookController::class,
    ],
    'wolf-helloasso.webhook_bus' => [
        'class' => \App\HelloAsso\Webhook\WebhookBus::class,
    ],
    'wolf-helloasso.webhook.handler.receive_order' => [
        'class' => \App\HelloAsso\Webhook\Handler\ReceiveOrderHandler::class,
        'arguments' => ['@helper.string', '@use-case-bus'],
        'tags' => [
            [
                'name' => 'wolf-helloasso.webhook_handler',
                'value' => 'receive_order',
            ]
        ]
    ],
    'wolf-helloasso.webhook.handler.receive_payment' => [
        'class' => \App\HelloAsso\Webhook\Handler\ReceivePaymentHandler::class,
        'arguments' => ['@use-case-bus'],
        'tags' => [
            [
                'name' => 'wolf-helloasso.webhook_handler',
                'value' => 'receive_payment',
            ]
        ]
    ],
    'wolf-helloasso.controller.request' => [
        'class' => \App\HelloAsso\Controller\RequestController::class,
    ],
    'wolf-helloasso.use-case.sync_requests' => [
        'class' => \App\HelloAsso\UseCase\SyncRequestsUseCase::class,
        'arguments' => ['@wolf-helloasso.sdk', '@entity.manager'],
        'tags' => [
            ['name' => 'use-case', 'value' => 'wolf-helloasso.sync_requests']
        ],
    ],
];