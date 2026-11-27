<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-subscription-discipline',
        '/membership/subscription-disciplines',
        \App\Membership\Controller\SubscriptionDisciplineController::class,
        [
            'permission' => [
                'view' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW],
                'edit' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
            ],
        ]
    ),
    []
);
