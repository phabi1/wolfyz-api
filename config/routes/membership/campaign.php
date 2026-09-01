<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-campaign',
        '/membership/campaigns',
        \App\Membership\Controller\CampaignController::class
    ),
    [
        'membership-campaign-settings-update' => [
            'path' => 'membership/campaigns/{campaign_id}/settings',
            'methods' => 'PUT',
            'controller' => [\App\Membership\Controller\CampaignController::class, 'updateCampaignSettings'],
            'requirements' => [
                'campaign_id' => '\d+'
            ]
        ],
        'membership-campaign-current-wheels' => [
            'path' => 'membership/campaigns/{campaign_id}/current-wheels',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\CampaignController::class, 'currentWheels'],
            'requirements' => [
                'campaign_id' => '\d+'
            ]
        ],
        'membership-campaign-next-wheels' => [
            'path' => 'membership/campaigns/{campaign_id}/next-wheels',
            'methods' => 'GET',
            'controller' => [\App\Membership\Controller\CampaignController::class, 'nextWheels'],
            'requirements' => [
                'campaign_id' => '\d+'
            ]
        ]
    ]
);