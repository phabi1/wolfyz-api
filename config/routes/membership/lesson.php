<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-lesson',
        '/membership/campaigns/{campaign_id}/lessons',
        \App\Membership\Controller\LessonController::class
    ),
    []
);