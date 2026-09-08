<?php
return array_merge(
    [
        'welcome' => [
            'path' => '',
            'controller' => ['App\Welcome\Controller\DefaultController', 'index'],
            'auth' => 'public'
        ],
    ],
    require_once __DIR__ . '/routes/file.php',
    require_once __DIR__ . '/routes/billing.php',
    require_once __DIR__ . '/routes/event.php',
    require_once __DIR__ . '/routes/membership.php',
    require_once __DIR__ . '/routes/hello-asso.php',
);