<?php

namespace App\Core\Security\Strategy;

use Symfony\Component\HttpFoundation\Request;

class PublicStrategy implements StrategyInterface
{
    public function authenticate(Request $request): bool
    {
        // Public strategy does not require authentication
        return true;
    }
}