<?php

namespace App\Core\Security\Strategy;

use App\Core\Security\Identity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authenticates a trusted server (the WordPress site) with the shared API key
 * ("X-Api-Key" header). It is only accepted on the routes that allow it (see
 * config/security.php) and grants no permission.
 */
class ApiKeyStrategy implements StrategyInterface
{
    public function __construct(private readonly ?string $apiKey)
    {
    }

    public function authenticate(Request $request): bool
    {
        $apiKey = (string) $request->headers->get('x-api-key', '');
        if ($this->apiKey === null || $this->apiKey === '' || !hash_equals($this->apiKey, $apiKey)) {
            return false;
        }
        $request->attributes->set('_identity', new Identity('api-key'));

        return true;
    }
}
