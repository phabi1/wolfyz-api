<?php

return [
    'hello-asso-webhooks-handle' => [
        'path' => '/hello-asso/webhooks',
        'controller' => ['App\HelloAsso\Controller\DefaultController', 'handle'],
        'methods' => ['POST'],
    ],
];