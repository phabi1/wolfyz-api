<?php

return array_merge(
    require __DIR__ . '/services/core.php',
    require __DIR__ . '/services/file.php',
    require __DIR__ . '/services/billing.php',
    require __DIR__ . '/services/event.php',
    require __DIR__ . '/services/helloasso.php',
    require __DIR__ . '/services/membership.php',
);