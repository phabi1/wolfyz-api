<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-lesson',
        '/membership/campaigns/{campaign_id}/lessons',
        \App\Membership\Controller\LessonController::class,
        [
            'permission' => [
                'view' => \App\Membership\Permissions::ANY_VIEW,
                'edit' => [\App\Membership\Permissions::CAMPAIGN_EDIT],
            ],
        ]
    ),
    []
);