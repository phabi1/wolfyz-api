# Club API

## Security

Every request goes through `App\Core\Security\Firewall`. Each route declares
who may call it, next to its path and controller (`config/routes`):

- `auth`: accepted authentication strategies, any of them (default `['jwt']`):
  - **`jwt`**: users of the club, with an access token of the auth server
    (`Authorization: Bearer <token>`). The token must be signed by one of the
    issuer's keys (JWKS, cached in `cache/jwks.json`), be valid, come from the
    configured issuer and have the `api` scope;
  - **`api-key`**: the WordPress site (`X-Api-Key` header), only on the routes
    of its public forms;
  - **`public`**: signed file URLs, invoice download, HelloAsso webhooks.
- `permission`: with `jwt`, the user needs at least one of these permissions
  (token's `permissions` claim). Not checked for `api-key` and `public`.

```php
'membership-request-approve' => [
    'path' => '/membership/campaigns/{campaign_id}/requests/{request_id}/approve',
    'methods' => 'POST',
    'controller' => [RequestController::class, 'approve'],
    'permission' => [\App\Membership\Permissions::REQUEST_EDIT],
],
```

`Routes::create()` takes a `permission` option, `['view' => ..., 'edit' => ...]`:
the GET routes it creates need the `view` permission, the others the `edit` one.

A `jwt` route without permission is denied; `test/Core/Security/RouteAccessTest.php`
fails when a route declares none.

Responses: 401 when no accepted authentication succeeds, 403 when the user
lacks the permission.

Parameters (`security.firewall.strategies`):

| Parameter | Description |
|-----------|-------------|
| `api-key.secret` | API key of the WordPress site; keep it secret |
| `jwt.issuer` | issuer of the access tokens, e.g. `https://auth.example.com` (no issuer: every token is refused) |
| `jwt.jwks_uri` | JWKS of the issuer, by default `<issuer>/.well-known/jwks.json` |

Apache must pass the `Authorization` header to PHP (always needed under
CGI/FastCGI). In `public/.htaccess`, inside `<IfModule mod_rewrite.c>`:

```apache
RewriteCond %{HTTP:Authorization} .
RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

## Tests

```sh
vendor/bin/phpunit
```
