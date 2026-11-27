<?php

namespace App\Core\Security;

use App\Core\Di\Locator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Authenticates the request with the strategies its route accepts, then checks
 * the permissions of the user. Declared on each route (config/routes):
 *
 * - "auth": accepted strategies, any of them (default: ["jwt"]):
 *   "jwt" (access token of the auth server: a user of the club, or the
 *   WordPress site with a client_credentials token), "public" (anyone);
 * - "permission": with "jwt", the caller needs at least one of them. Not
 *   checked for "public";
 * - "self": true for the routes of a user's own data (e.g. their event
 *   registrations): any signed-in user, no permission, but not an OAuth client
 *   acting on its own behalf. The controller limits the data to the user.
 *
 * A "jwt" route with neither permission nor "self" is denied (fail closed).
 */
class Firewall
{
    public const GRANTED = 200;
    public const UNAUTHENTICATED = 401;
    public const FORBIDDEN = 403;

    public function __construct(private readonly Locator $strategies)
    {
    }

    /**
     * @return int one of GRANTED, UNAUTHENTICATED, FORBIDDEN
     */
    public function check(Request $request, ?Route $route): int
    {
        $auth = (array) ($route?->getOption('_auth') ?? ['jwt']);
        $permission = (array) ($route?->getOption('_permission') ?? []);
        $self = (bool) ($route?->getOption('_self') ?? false);

        foreach ($auth as $strategy) {
            if (!$this->strategies->get($strategy)->authenticate($request)) {
                continue;
            }
            if ($strategy !== 'jwt') {
                return self::GRANTED;
            }
            $identity = $request->attributes->get('_identity');
            $granted = $identity instanceof Identity && ($self
                ? $identity->isUser()
                : $permission !== [] && $identity->hasPermission(...$permission));

            return $granted ? self::GRANTED : self::FORBIDDEN;
        }

        return self::UNAUTHENTICATED;
    }
}
