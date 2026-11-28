<?php

return [
    'db' => [
        'dsn' => 'mysql:host=' . getenv('DB_HOST') . ':' . getenv('DB_PORT') . ';dbname=' . (getenv('DB_NAME_TEST') ?: getenv('DB_NAME')),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        ],
    ],
    'mail' => [
        'from' => ['email' => getenv('MAIL_FROM'), 'name' => getenv('MAIL_FROM_NAME')],
        'smtp' => [
            'host' => getenv('MAIL_HOST'),
            'port' => getenv('MAIL_PORT'),
            'username' => getenv('MAIL_USERNAME'),
            'password' => getenv('MAIL_PASSWORD'),
            'encryption' => getenv('MAIL_ENCRYPTION') === 'false' ? false : getenv('MAIL_ENCRYPTION'),
        ],
    ],
    'media' => [
        'server' => getenv('MEDIA_SERVER'),
    ],
    'security' => [
        'firewall' => [
            'strategies' => [
                'jwt' => [
                    'issuer' => 'https://auth.test',
                    'jwks_uri' => null,
                ],
            ],
        ],
    ],
    'file' => [
        'server' => 'https://files.test',
        'bucket' => 'api',
        'key_id' => 'FKTESTAPI',
        'secret' => 'test-secret-test-secret',
    ],
    'site_contact_url' => getenv('SITE_CONTACT_URL'),
    'site_membership_request_edit_url' => getenv('SITE_MEMBERSHIP_REQUEST_EDIT_URL'),
    'site_membership_request_payment_url' => getenv('SITE_MEMBERSHIP_REQUEST_PAYMENT_URL'),
];
