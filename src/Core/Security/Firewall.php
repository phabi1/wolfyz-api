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
 *   "jwt" (user of the club, access token of the auth server),
 *   "api-key" (trusted server: the WordPress site), "public" (anyone);
 * - "permission": with "jwt", the user needs at least one of them. Not checked
 *   for "api-key" and "public".
 *
 * A "jwt" route without permission is denied (fail closed).
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

        foreach ($auth as $strategy) {
            if (!$this->strategies->get($strategy)->authenticate($request)) {
                continue;
            }
            if ($strategy !== 'jwt') {
                return self::GRANTED;
            }
            $identity = $request->attributes->get('_identity');
            $granted = $identity instanceof Identity
                && $permission !== []
                && $identity->hasPermission(...$permission);

            return $granted ? self::GRANTED : self::FORBIDDEN;
        }

        return self::UNAUTHENTICATED;
    }
}
