<?php

return [
    'hello-asso-webhooks-handle' => [
        'path' => '/helloasso/webhooks',
        'controller' => [App\HelloAsso\Controller\WebhookController::class, 'handle'],
        'methods' => ['POST'],
        'auth' => 'public'
    ],
    'hello-asso-request-sync' => [
        'path' => '/helloasso/request/sync',
        'controller' => [App\HelloAsso\Controller\RequestController::class, 'sync'],
        'methods' => ['POST'],
    ],
];