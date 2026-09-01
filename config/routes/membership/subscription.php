<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-subscription',
        '/membership/campaigns/{campaign_id}/subscriptions',
        \App\Membership\Controller\SubscriptionController::class
    ),
    [
        'membership-subscription-import' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/import',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'import'],
            'permission_callback' => '__return_true'
        ],
        'membership-subscription-export' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/export',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'export'],
            'permission_callback' => '__return_true'
        ]
    ]
);