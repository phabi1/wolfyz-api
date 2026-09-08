<?php

return [
    'file-upload' => [
        'path' => 'file/upload',
        'controller' => [\App\File\Controller\FileController::class, 'upload'],
        'methods' => ['PUT'],
        'defaults' => [
            'auth' => 'public'
        ],
    ],
    'file-remove' => [
        'path' => 'file/remove',
        'controller' => [\App\File\Controller\FileController::class, 'remove'],
        'methods' => ['DELETE'],
        'defaults' => [
            'auth' => 'public'
        ],
    ],
    'file-download' => [
        'path' => 'file/download',
        'controller' => [\App\File\Controller\FileController::class, 'download'],
        'methods' => ['GET'],
        'defaults' => [
            'auth' => 'public'
        ],
    ],
];