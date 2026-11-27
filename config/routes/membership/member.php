<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-member',
        '/membership/members',
        \App\Membership\Controller\MemberController::class,
        [
            'permission' => [
                // Members are looked up from the requests too.
                'view' => [\App\Membership\Permissions::REQUEST_VIEW, \App\Membership\Permissions::SUBSCRIPTION_VIEW],
                'edit' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
            ],
        ]
    ),
    [
        "membership-member-import" => [
            'path' => '/membership/members/import',
            'methods' => ['POST'],
            'controller' => [\App\Membership\Controller\MemberController::class, 'import'],
            'permission' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
        ],
        "membership-member-exists" => [
            'path' => '/membership/members/exists',
            'methods' => ['GET'],
            'controller' => [\App\Membership\Controller\MemberController::class, 'exists'],
            'permission' => [\App\Membership\Permissions::REQUEST_VIEW, \App\Membership\Permissions::SUBSCRIPTION_VIEW],
        ],
        "membership-member-hash" => [
            'path' => '/membership/members/hash',
            'methods' => ['POST'],
            'controller' => [\App\Membership\Controller\MemberController::class, 'hash'],
            'permission' => [\App\Membership\Permissions::REQUEST_VIEW, \App\Membership\Permissions::SUBSCRIPTION_VIEW],
        ],
    ]
);