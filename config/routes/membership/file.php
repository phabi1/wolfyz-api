<?php

return [
    'membership-file-upload-post' => [
        'path' => '/membership/file/upload',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\FileController::class, 'upload'],
    ],
    'membership-file-upload-delete' => [
        'path' => '/membership/file/remove',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\FileController::class, 'remove'],
    ],
    'membership-file-download-get' => [
        'path' => '/membership/file/download',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\FileController::class, 'download'],
    ],
];