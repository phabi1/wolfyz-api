<?php

return [
    'db' => [
        'dsn' => 'mysql:host=' . getenv('DB_HOST') . ':' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        ],
    ],
];