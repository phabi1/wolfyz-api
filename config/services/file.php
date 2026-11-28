<?php

return [
    // Presigned URLs of the files service (config: file.*)
    'file.presigned-url' => [
        'class' => \App\File\Presign\PresignedUrlService::class,
        'arguments' => ['!file.server', '!file.bucket', '!file.key_id', '!file.secret']
    ]
];
