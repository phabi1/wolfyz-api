<?php

return [
    'membership-file-upload-post' => [
        'path' => '/membership/file/upload',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\FileController::class, 'upload'],
        'auth' => ['jwt', 'api-key'],
        'permission' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
    ],
    'membership-file-upload-delete' => [
        'path' => '/membership/file/remove',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\FileController::class, 'remove'],
        'auth' => ['jwt', 'api-key'],
        'permission' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT],
    ],
    'membership-file-download-get' => [
        'path' => '/membership/file/download',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\FileController::class, 'download'],
        'auth' => ['jwt', 'api-key'],
        'permission' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW],
    ],
];