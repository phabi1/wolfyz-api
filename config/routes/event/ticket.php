<?php
return array_merge(\App\Core\Rest\Routes::create(
    'event-ticket',
    '/event/events/{event_id}/tickets',
    \App\Event\Controller\TicketController::class
));