<?php

if (!defined('APP_DIR')) {
    define('APP_DIR', __DIR__);
}

if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

$config = require __DIR__ . '/config/parameters.'.APP_ENV.'.php';

/**
 * Database settings from the parameters file: either host/port/name keys or
 * a PDO dsn string (mysql:host=HOST[:PORT];port=PORT;dbname=NAME).
 */
$db = $config['db'] ?? [];
if (empty($db['name']) && !empty($db['dsn'])) {
    $dsn = preg_replace('/^[a-z]+:/i', '', $db['dsn']);
    foreach (explode(';', $dsn) as $part) {
        [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
        $key = strtolower(trim($key));
        $value = trim($value);
        if ($key === 'host' && strpos($value, ':') !== false) {
            [$value, $port] = explode(':', $value, 2);
            $db['port'] ??= $port;
        }
        $map = ['host' => 'host', 'port' => 'port', 'dbname' => 'name', 'unix_socket' => 'unix_socket'];
        if (isset($map[$key]) && $value !== '') {
            $db[$map[$key]] ??= $value;
        }
    }
}
if (empty($db['name'])) {
    fwrite(STDERR, 'No database name in config/parameters.' . APP_ENV . '.php. If it reads environment variables set by the web server (SetEnv), they are not available on the command line.' . PHP_EOL);
    exit(1);
}

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
                'host' => $db['host'] ?? 'localhost',
                'port' => (int) ($db['port'] ?? 3306),
                'name' => $db['name'],
                'user' => $db['username'] ?? '',
                'pass' => $db['password'] ?? '',
                'charset' => 'utf8',
                'unix_socket' => $db['unix_socket'] ?? null,
            ],
        ],
        'version_order' => 'creation'
    ];
