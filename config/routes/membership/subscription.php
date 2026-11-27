<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-subscription',
        '/membership/campaigns/{campaign_id}/subscriptions',
        \App\Membership\Controller\SubscriptionController::class,
        [
            'permission' => [
                'view' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW],
                'edit' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
            ],
        ]
    ),
    [
        'membership-subscription-import' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/import',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'import'],
            'permission' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
        ],
        'membership-subscription-export' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/export',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'export'],
            'permission' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW],
        ],
        'membership-subscription-sync' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/sync',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'sync'],
            'permission' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
        ],
        'membership-subscription-license-taken' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/license-taken',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'licenseTaken'],
            'permission' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
        ],
        'membership-subscription-count-by-gender' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/count/gender',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'countByGender'],
            'permission' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW],
        ],
        'membership-subscription-count-by-age' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/count/age',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'countByAge'],
            'permission' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW],
        ],
        'membership-subscription-count-by-license-type' => [
            'path' => 'membership/campaigns/{campaign_id}/subscriptions/count/license-type',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\SubscriptionController::class, 'countByLicenseType'],
            'permission' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW],
        ],
    ]
);