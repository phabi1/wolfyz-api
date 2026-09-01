<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-wheel-assignment',
        '/membership/wheels-assignments',
        \App\Membership\Controller\WheelAssignmentController::class
    ),
    []
);