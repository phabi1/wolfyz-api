<?php
return array_merge(\App\Core\Rest\Routes::create(
    'event-participant',
    '/event/events/{event_id}/participants',
    \App\Event\Controller\ParticipantController::class
));