# Club API

## Security

Every request goes through `App\Core\Security\Firewall`. Each route declares
who may call it, next to its path and controller (`config/routes`):

- `auth`: accepted authentication strategies, any of them (default `['jwt']`):
  - **`jwt`**: access token of the auth server (`Authorization: Bearer <token>`),
    either of a user of the club, or of the WordPress site (OAuth client
    `wordpress`, `client_credentials` grant). The token must be signed by one
    of the issuer's keys (JWKS, cached in `cache/jwks.json`), be valid, come
    from the configured issuer and have the `api` scope;
  - **`public`**: signed file URLs, invoice download, HelloAsso webhooks.
- `permission`: with `jwt`, the caller needs at least one of these permissions
  (token's `permissions` claim: roles of the user, or of the OAuth client).
  The website forms need `membership.registration` / `event.registration`
  (role "Site web" of the `wordpress` client). Not checked for `public`.
- `self` (true): the route returns the caller's own data (e.g.
  `GET /event/me/registrations`): any signed-in user, no permission, but not
  an OAuth client acting on its own behalf. The controller limits the data to
  the user (`sub`, and the `email` claim only when `email_verified`).

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

A `jwt` route with neither `permission` nor `self` is denied; `test/Core/Security/RouteAccessTest.php`
fails when a route declares none.

Responses: 401 when no accepted authentication succeeds, 403 when the caller
lacks the permission.

Parameters (`security.firewall.strategies`):

| Parameter | Description |
|-----------|-------------|
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
