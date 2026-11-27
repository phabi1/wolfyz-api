<?php

return [
    'membership-registration-get' => [
        'path' => 'membership/campaigns/{campaign_id}/registration',
        'methods' => ['GET'],
        'controller' => [\App\Membership\Controller\RegistrationController::class, 'registration'],
        'permission' => [\App\Membership\Permissions::REGISTRATION],
        'requirements' => [
            'campaign_id' => '\d+'
        ]
    ],
    'membership-registration-calculate-total' => [
        'path' => 'membership/campaigns/{campaign_id}/registration/calculate-total',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\RegistrationController::class, 'calculateTotal'],
        // Website form and club (pricing a request).
        'permission' => [\App\Membership\Permissions::REQUEST_VIEW, \App\Membership\Permissions::REGISTRATION],
        'requirements' => [
            'campaign_id' => '\d+'
        ]
    ],
    'membership-register' => [
        'path' => 'membership/campaigns/{campaign_id}/register',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\RegistrationController::class, 'register'],
        'permission' => [\App\Membership\Permissions::REGISTRATION],
        'requirements' => [
            'campaign_id' => '\d+'
        ]
    ]
];