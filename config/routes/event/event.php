<?php
return array_merge(
    [
        'event-event-copy' => [
            'path' => '/event/events/{id}/copy',
            'methods' => ['POST'],
            'controller' => [\App\Event\Controller\EventController::class, 'copy'],
            'requirements' => [
                'id' => '[0-9]+',
            ],
        ],
    ],
    \App\Core\Rest\Routes::create(
        'event-event',
        '/event/events',
        \App\Event\Controller\EventController::class
    )
);