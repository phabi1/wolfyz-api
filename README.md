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
  - **`public`**: invoice download, HelloAsso webhooks.
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

## Files

Uploaded files (medical certificates, event registration files…) are stored
by the **files** service, in a private bucket. The API never handles their
content: it hands out presigned URLs (15 minutes) to the club and the website,
which upload (`PUT`), download (`GET`) and delete (`DELETE`) the file on the
files service directly. The key of the object
(`membership/<random>/<name>`) is what the API stores.

Parameters (`file`):

| Parameter | Description |
|-----------|-------------|
| `server` | public URL of the files service, e.g. `https://files.example.com` |
| `bucket` | bucket of the API (private), `api` by default |
| `key_id`, `secret` | access key of the API on the files service (`bin/console key:create api --bucket=api` there) |

### Files stored by HelloAsso

The sync of the HelloAsso export stores links to HelloAsso
(`https://docs.helloasso.com/customFieldsAnswer/<id>`) in the file fields of
the subscriptions (`medical_certificate`, `identity_photo`, `medical_form`).
This command copies those files to the files service and points the
subscriptions to the copies:

```sh
bin/console membership:transfer-helloasso-files --dry-run          # list, change nothing
bin/console membership:transfer-helloasso-files [--campaign=<id>]
```

It downloads each link anonymously, then with the HelloAsso API token if
refused (token requested once), pauses `--delay` seconds between files (1 by
default), and can be run again (only HelloAsso links are transferred). It
stops at the first rate limit (HTTP 429) or access refused even with the
token: HelloAsso rate limits the server's IP, which also blocks the payments
for a while. Later
syncs keep the transferred files instead of the HelloAsso links. Run it after
each sync.

## Tests

```sh
vendor/bin/phpunit
```
