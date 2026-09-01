<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-wheel',
        '/membership/wheels',
        \App\Membership\Controller\WheelController::class
    ),
    []
);