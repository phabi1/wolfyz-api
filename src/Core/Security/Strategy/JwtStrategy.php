<?php

namespace App\Core\Security\Strategy;

use App\Core\Security\Identity;
use App\Core\Security\Jwks;
use App\Core\Watchdog\WatchdogAwareInterface;
use App\Core\Watchdog\WatchdogAwareTrait;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authenticates a user with an access token of the auth server
 * ("Authorization: Bearer <token>"): RS256 signature checked with the auth
 * server's public keys, validity period, issuer and "api" scope. The user's
 * permissions come from the token's "permissions" claim.
 *
 * Fails closed: a rejected token is logged in the watchdog (error for a
 * configuration problem, warning for a bad token), except when there is no
 * token or it has expired (normal client behaviour).
 */
class JwtStrategy implements StrategyInterface, WatchdogAwareInterface
{
    use WatchdogAwareTrait;

    /** Tolerated clock difference between the servers, in seconds. */
    private const LEEWAY = 30;

    public function __construct(
        private readonly Jwks $jwks,
        private readonly ?string $issuer,
        private readonly string $requiredScope = 'api',
    ) {
    }

    public function authenticate(Request $request): bool
    {
        $identity = $this->identity($request);
        if ($identity === null) {
            return false;
        }
        $request->attributes->set('_identity', $identity);

        return true;
    }

    private function identity(Request $request): ?Identity
    {
        if ($this->issuer === null || $this->issuer === '') {
            return $this->reject('JWT: no issuer configured (security.firewall.strategies.jwt.issuer)', 'error');
        }

        $header = (string) $request->headers->get('Authorization', '');
        if (!preg_match('/^Bearer +([A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+)$/', $header, $matches)) {
            return null;
        }

        $claims = $this->decode($matches[1]);
        if ($claims === null) {
            return null;
        }

        // Access token of this issuer only: ID tokens have no "scopes" claim.
        $scopes = $claims['scopes'] ?? null;
        $subject = $claims['sub'] ?? null;
        $permissions = $claims['permissions'] ?? [];
        if (($claims['iss'] ?? null) !== $this->issuer) {
            return $this->reject('JWT: token of another issuer', 'warning', [
                'iss' => $claims['iss'] ?? null,
                'expected' => $this->issuer,
            ]);
        }
        if (!is_array($scopes) || !in_array($this->requiredScope, $scopes, true)) {
            return $this->reject(sprintf('JWT: token without the "%s" scope', $this->requiredScope), 'warning', [
                'sub' => $subject,
                'scopes' => $scopes,
            ]);
        }
        if (!is_string($subject) || $subject === '' || !is_array($permissions)) {
            return $this->reject('JWT: token without subject or with invalid permissions', 'warning');
        }

        return new Identity($subject, array_values(array_filter($permissions, 'is_string')));
    }

    /** Logs why a token is rejected; never breaks the authentication. */
    private function reject(string $message, string $level, array $data = []): null
    {
        try {
            $this->watchdogService?->{$level}($message, $data);
        } catch (\Throwable) {
            // e.g. database down: the request is rejected anyway
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function decode(string $token): ?array
    {
        $previousLeeway = JWT::$leeway;
        JWT::$leeway = self::LEEWAY;
        try {
            foreach ([false, true] as $reload) {
                $keys = $this->jwks->keys($reload);
                if ($keys === []) {
                    if ($reload) {
                        return $this->reject('JWT: no public key of the auth server', 'error', [
                            'jwks_uri' => $this->jwks->getUri(),
                            'reason' => $this->jwks->getError(),
                        ]);
                    }
                    continue;
                }
                try {
                    return json_decode(json_encode(JWT::decode($token, $keys)), true);
                } catch (\UnexpectedValueException $e) {
                    // Unknown key id: the auth server may have rotated its keys.
                    if (!$reload && str_contains($e->getMessage(), '"kid"')) {
                        continue;
                    }
                    if ($e instanceof ExpiredException) {
                        return null; // the client refreshes its token
                    }
                    return $this->reject('JWT: invalid token: ' . $e->getMessage(), 'warning', [
                        'reason' => $this->jwks->getError(),
                    ]);
                } catch (\Throwable $e) {
                    return $this->reject('JWT: invalid token: ' . $e->getMessage(), 'warning');
                }
            }

            return null;
        } finally {
            JWT::$leeway = $previousLeeway;
        }
    }
}
