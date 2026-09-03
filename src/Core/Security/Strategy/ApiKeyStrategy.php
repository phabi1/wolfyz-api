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
        $apiKey = $request->headers->get('X-API-KEY');
        // Implement your API key validation logic here
        return $apiKey === $this->apiKey;
    }
}