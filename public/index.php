<?php

require __DIR__ . '/../vendor/autoload.php';

define('APP_DIR', __DIR__ . '/..');
define('CONFIG_DIR', APP_DIR . '/config');
define('CACHE_DIR', APP_DIR . '/cache');

\App\Application::run();