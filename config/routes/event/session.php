<?php
return array_merge(\App\Core\Rest\Routes::create(
    'event-session',
    '/event/events/{event_id}/sessions',
    \App\Event\Controller\SessionController::class,
    [
        'permission' => [
            'view' => ['event.view'],
            'edit' => ['event.edit'],
        ],
    ]
));