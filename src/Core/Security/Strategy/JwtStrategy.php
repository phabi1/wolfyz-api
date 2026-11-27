<?php

namespace App\Core\Security\Strategy;

use App\Core\Security\Identity;
use App\Core\Security\Jwks;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authenticates a user with an access token of the auth server
 * ("Authorization: Bearer <token>"): RS256 signature checked with the auth
 * server's public keys, validity period, issuer and "api" scope. The user's
 * permissions come from the token's "permissions" claim.
 */
class JwtStrategy implements StrategyInterface
{
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
            return null; // not configured: fail closed
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
        if (($claims['iss'] ?? null) !== $this->issuer
            || !is_array($scopes) || !in_array($this->requiredScope, $scopes, true)
            || !is_string($subject) || $subject === ''
            || !is_array($permissions)
        ) {
            return null;
        }

        // client_credentials tokens: the client is its own subject.
        $audience = (array) ($claims['aud'] ?? []);
        $email = $claims['email'] ?? null;

        return new Identity(
            $subject,
            array_values(array_filter($permissions, 'is_string')),
            !in_array($subject, $audience, true),
            is_string($email) && $email !== '' ? $email : null,
            ($claims['email_verified'] ?? false) === true,
        );
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
                    continue;
                }
                try {
                    return json_decode(json_encode(JWT::decode($token, $keys)), true);
                } catch (\UnexpectedValueException $e) {
                    // Unknown key id: the auth server may have rotated its keys.
                    if (!$reload && str_contains($e->getMessage(), '"kid"')) {
                        continue;
                    }
                    return null;
                } catch (\Throwable) {
                    return null;
                }
            }

            return null;
        } finally {
            JWT::$leeway = $previousLeeway;
        }
    }
}
