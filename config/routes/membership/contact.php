<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-contact',
        '/membership/campaigns/{campaign_id}/contacts',
        \App\Membership\Controller\ContactController::class
    ),
    []
);