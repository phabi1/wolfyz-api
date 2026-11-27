<?php
return array_merge(
    [
        'billing-get-bank-amount' => [
            'path' => '/billing/bank-amount',
            'methods' => ['GET'],
            'controller' => [\App\Billing\Controller\BillingController::class, 'bankAmount'],
            'permission' => ['billing.view'],
        ],
    ],
    \App\Core\Rest\Routes::create(
        'billing-payment',
        '/billing/payments',
        \App\Billing\Controller\PaymentController::class,
        [
            'permission' => [
                'view' => ['billing.view'],
                'edit' => ['billing.edit'],
            ],
        ]
    )
);