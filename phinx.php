<?php

if (!defined('APP_DIR')) {
    define('APP_DIR', __DIR__);
}

if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

$config = require __DIR__ . '/config/parameters.'.APP_ENV.'.php';

return
    [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
            'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
        ],
        'environments' => [
            'default_migration_table' => 'wolf_phinxlog',
            'default_environment' => 'main',
            'main' => [
                'adapter' => 'mysql',
                'host' => $config['db']['host'],
                'port' => $config['db']['port'],
                'name' => $config['db']['name'],
                'user' => $config['db']['username'],
                'pass' => $config['db']['password'],
                'charset' => 'utf8',
            ],
        ],
        'version_order' => 'creation'
    ];
