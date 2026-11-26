<?php

return array_merge(
    \App\Core\Rest\Routes::create(
        'membership-discipline',
        '/membership/disciplines',
        \App\Membership\Controller\DisciplineController::class
    ),
    []
);
