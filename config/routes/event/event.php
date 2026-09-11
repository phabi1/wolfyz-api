<?php
return array_merge(\App\Core\Rest\Routes::create(
    'event-event',
    '/event/events',
    \App\Event\Controller\EventController::class
));