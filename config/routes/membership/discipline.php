<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-discipline',
        '/membership/disciplines',
        \App\Membership\Controller\DisciplineController::class,
        [
            'permission' => [
                'view' => \App\Membership\Permissions::ANY_VIEW,
                'edit' => [\App\Membership\Permissions::CAMPAIGN_EDIT],
            ],
        ]
    ),
    []
);
