<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-session',
        '/membership/campaigns/{campaign_id}/sessions',
        \App\Membership\Controller\SessionController::class,
        [
            'permission' => [
                'view' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW],
                'edit' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
            ],
        ]
    ),
    []
);