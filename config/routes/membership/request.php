<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-request',
        '/membership/campaigns/{campaign_id}/requests',
        \App\Membership\Controller\RequestController::class,
        [
            'actions' => \App\Core\Rest\Routes::ROUTE_ITEMS | \App\Core\Rest\Routes::ROUTE_ITEM,
        ]
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
        ],
        'membership-request-invoice' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/invoice',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'invoice'],
            'requirements' => [
                'campaign_id' => '\\d+',
                'request_id' => '\\d+'
            ]
        ],
        'membership-request-send-invoice-email' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/invoice/send-email',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'sendInvoiceEmail'],
            'requirements' => [
                'campaign_id' => '\\d+',
                'request_id' => '\\d+'
            ]
        ],
        'membership-request-download-invoice' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/invoice/download',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\RequestController::class, 'downloadInvoice'],
            'defaults' => [
                'auth' => 'public'
            ],
            'requirements' => [
                'campaign_id' => '\\d+',
                'request_id' => '\\d+'
            ]
        ]
    ]
);