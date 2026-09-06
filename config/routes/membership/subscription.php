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
        ],
        'membership-subscription-export' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/export',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'export'],
        ],
        'membership-subscription-sync' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/sync',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'sync'],
        ],
    ]
);