<?php

return [
    'wolf-helloasso.sdk' => [
        'factory' => [\App\HelloAsso\Sdk\ClientFactory::class, 'create'],
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
        'arguments' => ['@helper.string'],
        'tags' => [
            [
                'name' => 'wolf-helloasso.webhook_handler',
                'value' => 'receive_order',
            ]
        ]
    ],
    'wolf-helloasso.webhook.handler.receive_payment' => [
        'class' => \App\HelloAsso\Webhook\Handler\ReceivePaymentHandler::class,
        'arguments' => ['@use_case_bus'],
        'tags' => [
            [
                'name' => 'wolf-helloasso.webhook_handler',
                'value' => 'receive_payment',
            ]
        ]
    ]
];