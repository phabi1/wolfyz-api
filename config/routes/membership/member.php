<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-member',
        '/membership/members',
        \App\Membership\Controller\MemberController::class
    ),
    [
        "membership-member-import" => [
            'path' => '/membership/members/import',
            'methods' => ['POST'],
            'controller' => [\App\Membership\Controller\MemberController::class, 'import'],
        ],
        "membership-member-exists" => [
            'path' => '/membership/members/exists',
            'methods' => ['GET'],
            'controller' => [\App\Membership\Controller\MemberController::class, 'exists'],
        ],
        "membership-member-hash" => [
            'path' => '/membership/members/hash',
            'methods' => ['POST'],
            'controller' => [\App\Membership\Controller\MemberController::class, 'hash'],
        ],
    ]
);