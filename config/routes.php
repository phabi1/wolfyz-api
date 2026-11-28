<?php
return array_merge(
    [
        'welcome' => [
            'path' => '',
            'controller' => ['App\Welcome\Controller\DefaultController', 'index'],
            'auth' => ['public'],
        ],
    ],
    require __DIR__ . '/routes/billing.php',
    require __DIR__ . '/routes/event.php',
    require __DIR__ . '/routes/membership.php',
    require __DIR__ . '/routes/hello-asso.php',
);