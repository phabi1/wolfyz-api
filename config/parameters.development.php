<?php

return [
    'base_url' => getenv('BASE_URL'),
    'db' => [
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'name' => getenv('DB_NAME'),
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        ],
    ],
    'mail' => [
        'from' => ['email' => getenv('MAIL_FROM'), 'name' => getenv('MAIL_FROM_NAME')],
        "smtp" => [
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
                // Access tokens of the auth server. The JWKS URI defaults to
                // the issuer's; set it when the issuer URL is not reachable
                // from the API (e.g. Docker).
                'jwt' => [
                    'issuer' => getenv('OIDC_ISSUER'),
                    'jwks_uri' => getenv('OIDC_JWKS_URI') ?: rtrim((string) getenv('OIDC_ISSUER'), '/') . '/.well-known/jwks.json',
                ],
            ],
        ],

    ],
    'file' => [
        'secret' => getenv('FILE_SECRET')
    ],
    'helloasso' => [
        'organization_slug' => getenv('HELLOASSO_ORGANIZATION_SLUG'),
        'credentials' => [
            'api_key' => getenv('HELLOASSO_API_KEY'),
            'api_secret' => getenv('HELLOASSO_API_SECRET'),
        ],
        'options' => [
            'sandbox' => true,
            'auth_storage' => ['type' => 'filesystem', 'options' => ['path' => APP_DIR . '/auth.json']]
        ],
        'webhooks' => [
            'ips' => []
        ]
    ],
    'site_contact_url' => getenv('SITE_CONTACT_URL'),
    'site_event_return_url' => getenv('SITE_EVENT_RETURN_URL'),
    'site_membership_request_edit_url' => getenv('SITE_MEMBERSHIP_REQUEST_EDIT_URL'),
    'site_membership_request_payment_url' => getenv('SITE_MEMBERSHIP_REQUEST_PAYMENT_URL'),
];