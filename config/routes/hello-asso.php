<?php

return [
    'hello-asso-webhooks-handle' => [
        'path' => '/hello-asso/webhooks',
        'controller' => [App\HelloAsso\Controller\WebhookController::class, 'handle'],
        'methods' => ['POST'],
        'auth' => 'public'
    ],
    'hello-asso-request-sync' => [
        'path' => '/hello-asso/request/sync',
        'controller' => [App\HelloAsso\Controller\RequestController::class, 'sync'],
        'methods' => ['POST'],
    ],
];