<?php

return [
    'wolf-billing.controller.payment' => [
        'class' => \App\Billing\Controller\PaymentController::class
    ],
    'wolf-billing.payment_manager' => [
        'class' => \App\Billing\Payment\PaymentManager::class
    ],
    'wolf-billing.payment.strategy.check' => [
        'class' => \App\Billing\Payment\Strategy\Check::class,
        'arguments' => ['@settings'],
        'tags' => [
            ['name' => 'wolf_billing.payment.strategy', 'value' => 'check']
        ]
    ],
    'wolf-billing.payment.strategy.bank_transfer' => [
        'class' => \App\Billing\Payment\Strategy\BankTransfert::class,
        'arguments' => ['@settings'],
        'tags' => [
            ['name' => 'wolf_billing.payment.strategy', 'value' => 'bank_transfer']
        ]
    ],
    'wolf-billing.use-case.create_payment' => [
        'class' => \App\Billing\UseCase\CreatePaymentUseCase::class,
        'arguments' => ['@wolf-billing.payment_manager'],
        'tags' => [
            ['name' => 'use-case', 'value' => 'wolf-billing.create_payment']
        ],
        'shared' => false
    ],
    'wolf-billing.use-case.get_payments_by_meta' => [
        'class' => \App\Billing\UseCase\GetPaymentsByMetaUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use-case', 'value' => 'wolf-billing.get_payments_by_meta']
        ],
        'shared' => false
    ],
    'wolf-billing.use-case.add_payment' => [
        'class' => \App\Billing\UseCase\AddPaymentUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [
            ['name' => 'use-case', 'value' => 'wolf-billing.add_payment']
        ],
        'shared' => false
    ]
];