<?php

namespace App\Core\Security\Strategy;

use Symfony\Component\HttpFoundation\Request;

class ApiKeyStrategy implements StrategyInterface
{
    private $apiKey = '';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function authenticate(Request $request): bool
    {
        $apiKey = $request->headers->get('x-api-key');
        return $apiKey === $this->apiKey;
    }
}