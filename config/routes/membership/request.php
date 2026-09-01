<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-request',
        '/membership/campaigns/{campaign_id}/members',
        \App\Membership\Controller\RequestController::class
    ),
    [
        'membership-request-approve' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/approve',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'approve'],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-reject' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/reject',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'reject'],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-paid' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/paid',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'paid'],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-cancel' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/cancel',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'cancel'],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-history' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/history',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\RequestController::class, 'history'],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-resend-payment' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/resend-payment',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'resendPayment'],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ]
    ]
);