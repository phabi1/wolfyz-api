<?php

return [
    'membership-file-upload-post' => [
        'path' => '/membership/file/upload',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\FileController::class, 'upload'],
        // Club and website registration form.
        'permission' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT, \App\Membership\Permissions::REGISTRATION],
    ],
    'membership-file-upload-delete' => [
        'path' => '/membership/file/remove',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\FileController::class, 'remove'],
        // Club and website registration form.
        'permission' => [\App\Membership\Permissions::SUBSCRIPTION_EDIT, \App\Membership\Permissions::REGISTRATION],
    ],
    'membership-file-download-get' => [
        'path' => '/membership/file/download',
        'methods' => ['POST'],
        'controller' => [\App\Membership\Controller\FileController::class, 'download'],
        // Club and website registration form.
        'permission' => [\App\Membership\Permissions::SUBSCRIPTION_VIEW, \App\Membership\Permissions::REGISTRATION],
    ],
];