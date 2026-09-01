<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-period',
        '/membership/campaigns/{campaign_id}/periods',
        \App\Membership\Controller\PeriodController::class
    ),
    []
);