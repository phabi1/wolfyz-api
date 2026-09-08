<?php

return [
    'file' => [
        'class' => \App\File\Service\FileService::class
    ],
    'file.presigned-url' => [
        'class' => \App\File\Presign\PresignedUrlService::class,
        'arguments' => ['!base_url', '!file.secret']
    ]
];