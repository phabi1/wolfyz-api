<?php

namespace App\Core\Db;

use App\Core\Config\Parameters;
use \App\Core\Db\Db as Client;
use App\Core\Db\Handler\PdoHandler;

class DbFactory
{
    public static function create(Parameters $parameters)
    {
        $dbConfig = $parameters->get('db', []);

        if (!isset($dbConfig['dsn'])) {
            $dbConfig['dsn'] = 'mysql:host=' . ($dbConfig['host'] ?? '127.0.0.1') . ';port=' . ($dbConfig['port'] ?? '3306') . ';dbname=' . ($dbConfig['name'] ?? '');
        }

        $handler = new PdoHandler($dbConfig['dsn'] ?? '', $dbConfig['username'] ?? '', $dbConfig['password'] ?? '');
        $instance = new Client($handler);
        return $instance;
    }
}