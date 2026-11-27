<?php

return [
    'membership-dashboard' => [
        'path' => '/membership/dashboard',
        'controller' => [\App\Membership\Controller\DashboardController::class, 'source'],
        'permission' => \App\Membership\Permissions::ANY_VIEW,
        'methods' => 'GET'
    ]
];