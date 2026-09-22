<?php

use App\Event\Controller\RegistrationController;

return [
    'event-registration-info' => [
        'path' => '/event/registration/{id}/infos',
        'controller' => [RegistrationController::class, 'info'],
        'methods' => 'GET',
        'requirements' => [
            'id' => '\d+'
        ]
    ],
    'event-registration-check-subscription' => [
        'path' => '/event/registration/{id}/check-subscription',
        'controller' => [RegistrationController::class, 'checkSubscription'],
        'methods' => 'GET',
        'requirements' => [
            'id' => '\d+'
        ]
    ],
    'event-registration-register' => [
        'path' => '/event/registration/{id}/register',
        'controller' => [RegistrationController::class, 'register'],
        'methods' => 'POST',
        'requirements' => [
            'id' => '\d+'
        ]
    ],
    'event-registration-upload-file' => [
        'path' => '/event/registration/{id}/upload-file',
        'controller' => [RegistrationController::class, 'uploadFile'],
        'methods' => 'POST'
    ],
    'event-registration-remove-file' => [
        'path' => '/event/registration/{id}/remove-file',
        'controller' => [RegistrationController::class, 'removeFile'],
        'methods' => 'POST'
    ],
    'event-registration-download-file' => [
        'path' => '/event/registration/{id}/download-file',
        'controller' => [RegistrationController::class, 'downloadFile'],
        'methods' => 'POST'
    ],
];