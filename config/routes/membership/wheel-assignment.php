<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-wheel-assignment',
        '/membership/wheels-assignments',
        \App\Membership\Controller\WheelAssignmentController::class,
        [
            'permission' => [
                'view' => \App\Membership\Permissions::ANY_VIEW,
                'edit' => [\App\Membership\Permissions::CAMPAIGN_EDIT],
            ],
        ]
    ),
    []
);