<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-period',
        '/membership/campaigns/{campaign_id}/periods',
        \App\Membership\Controller\PeriodController::class
    ),
    [
        'membership-period-print' => [
            'path' => '/membership/campaigns/{campaign_id}/periods/{period_id}/print',
            'methods' => 'POST',
            'controller' => [\App\Membership\Controller\PeriodController::class, 'print'],
            'requirements' => [
                'campaign_id' => '\d+',
                'period_id' => '\d+'
            ]
        ],
    ]
);