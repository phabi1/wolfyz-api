<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-request',
        '/membership/campaigns/{campaign_id}/requests',
        \App\Membership\Controller\RequestController::class,
        [
            'actions' => \App\Core\Rest\Routes::ROUTE_ITEMS | \App\Core\Rest\Routes::ROUTE_ITEM,
            'permission' => [
                'view' => [\App\Membership\Permissions::REQUEST_VIEW],
                'edit' => [\App\Membership\Permissions::REQUEST_EDIT],
            ],
        ]
    ),
    [
        'membership-request-approve' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/approve',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'approve'],
            'permission' => [\App\Membership\Permissions::REQUEST_EDIT],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-reject' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/reject',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'reject'],
            'permission' => [\App\Membership\Permissions::REQUEST_EDIT],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-paid' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/paid',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'paid'],
            'permission' => [\App\Membership\Permissions::REQUEST_EDIT],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-cancel' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/cancel',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'cancel'],
            'permission' => [\App\Membership\Permissions::REQUEST_EDIT],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-history' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/history',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\RequestController::class, 'history'],
            'permission' => [\App\Membership\Permissions::REQUEST_VIEW],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-resend-payment' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/resend-payment',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'resendPayment'],
            'permission' => [\App\Membership\Permissions::REQUEST_EDIT],
            'requirements' => [
                'campaign_id' => '\d+',
                'request_id' => '\d+'
            ]
        ],
        'membership-request-invoice' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/invoice',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'invoice'],
            'permission' => [\App\Membership\Permissions::REQUEST_EDIT],
            'requirements' => [
                'campaign_id' => '\\d+',
                'request_id' => '\\d+'
            ]
        ],
        'membership-request-send-invoice-email' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/invoice/send-email',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\RequestController::class, 'sendInvoiceEmail'],
            'permission' => [\App\Membership\Permissions::REQUEST_EDIT],
            'requirements' => [
                'campaign_id' => '\\d+',
                'request_id' => '\\d+'
            ]
        ],
        'membership-request-download-invoice' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/invoice/download',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\RequestController::class, 'downloadInvoice'],
            'auth' => ['public'],
            'requirements' => [
                'campaign_id' => '\\d+',
                'request_id' => '\\d+'
            ]
        ],
        'membership-request-status-count' => [
            'path' => '/membership/campaigns/{campaign_id}/requests/status/count',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\RequestController::class, 'countStatus'],
            'permission' => [\App\Membership\Permissions::REQUEST_VIEW],
            'requirements' => [
                'campaign_id' => '\\d+',
                
            ]
        ]
    ]
);