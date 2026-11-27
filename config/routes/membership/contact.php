<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-contact',
        '/membership/campaigns/{campaign_id}/contacts',
        \App\Membership\Controller\ContactController::class,
        [
            'permission' => [
                'view' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW],
                'edit' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
            ],
        ]
    ),
    []
);