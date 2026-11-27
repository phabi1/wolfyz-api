<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-wheel',
        '/membership/wheels',
        \App\Membership\Controller\WheelController::class,
        [
            'permission' => [
                'view' => \App\Membership\Permissions::ANY_VIEW,
                'edit' => [\App\Membership\Permissions::CAMPAIGN_EDIT],
            ],
        ]
    ),
    []
);