<?php
return array_merge(\App\Core\Rest\Routes::create(
    'event-participant-field',
    '/event/events/{event_id}/participant-fields',
    \App\Event\Controller\ParticipantFieldController::class
));