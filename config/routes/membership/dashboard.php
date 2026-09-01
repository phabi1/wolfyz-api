<?php

return [
    'membership-dashboard' => [
        'path' => '/membership/dashboard',
        'controller' => [\App\Membership\Controller\DashboardController::class, 'source'],
        'methods' => 'GET'
    ]
];