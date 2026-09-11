<?php
return array_merge(
    require __DIR__ . '/event/event.php',
    require __DIR__ . '/event/ticket.php',
    require __DIR__ . '/event/session.php',
    require __DIR__ . '/event/participant-field.php',
);