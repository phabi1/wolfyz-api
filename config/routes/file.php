<?php

return [
    'file-upload' => [
        'path' => 'file/upload',
        'controller' => [\App\File\Controller\FileController::class, 'upload'],
        'auth' => ['public'],
        'methods' => ['PUT'],
    ],
    'file-remove' => [
        'path' => 'file/remove',
        'controller' => [\App\File\Controller\FileController::class, 'remove'],
        'auth' => ['public'],
        'methods' => ['DELETE'],
    ],
    'file-download' => [
        'path' => 'file/download',
        'controller' => [\App\File\Controller\FileController::class, 'download'],
        'auth' => ['public'],
        'methods' => ['GET'],
    ],
];