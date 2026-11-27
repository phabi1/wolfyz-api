<?php

return [
    'hello-asso-webhooks-handle' => [
        'path' => '/helloasso/webhooks',
        'controller' => [App\HelloAsso\Controller\WebhookController::class, 'handle'],
        'auth' => ['public'],
        'methods' => ['POST'],
    ],
    'hello-asso-request-sync' => [
        'path' => '/helloasso/request/sync',
        'controller' => [App\HelloAsso\Controller\RequestController::class, 'sync'],
        'permission' => ['membership.request.edit'],
        'methods' => ['POST'],
    ],
];