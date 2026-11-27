<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-campaign',
        '/membership/campaigns',
        \App\Membership\Controller\CampaignController::class,
        [
            'permission' => [
                'view' => \App\Membership\Permissions::ANY_VIEW,
                'edit' => [\App\Membership\Permissions::CAMPAIGN_EDIT],
            ],
        ]
    ),
    [
        'membership-campaign-settings-update' => [
            'path' => 'membership/campaigns/{campaign_id}/settings',
            'methods' => 'PUT',
            'controller' => [\App\Membership\Controller\CampaignController::class, 'updateCampaignSettings'],
            'permission' => [\App\Membership\Permissions::CAMPAIGN_EDIT],
            'requirements' => [
                'campaign_id' => '\d+'
            ]
        ],
        'membership-campaign-current-wheels' => [
            'path' => 'membership/campaigns/{campaign_id}/current-wheels',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\CampaignController::class, 'currentWheels'],
            'permission' => \App\Membership\Permissions::ANY_VIEW,
            'requirements' => [
                'campaign_id' => '\d+'
            ]
        ],
        'membership-campaign-next-wheels' => [
            'path' => 'membership/campaigns/{campaign_id}/next-wheels',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\CampaignController::class, 'nextWheels'],
            'permission' => \App\Membership\Permissions::ANY_VIEW,
            'requirements' => [
                'campaign_id' => '\d+'
            ]
        ]
    ]
);