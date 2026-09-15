<?php

return [
    'event-registration-info' => [
        'path' => '/event/registration/{id}/infos',
        'controller' => ['App\Event\Controller\RegistrationController', 'info'],
        'methods' => 'GET'
    ],
    'event-registration-register' => [
        'path' => '/event/registration/{id}/register',
        'controller' => ['App\Event\Controller\RegistrationController', 'register'],
        'methods' => 'POST'
    ],
];