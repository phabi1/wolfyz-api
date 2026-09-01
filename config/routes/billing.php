<?php
return array_merge(
    \App\Core\Rest\Routes::create(
        'billing-payment',
        '/billing/payments',
        \App\Billing\Controller\PaymentController::class
    )
);