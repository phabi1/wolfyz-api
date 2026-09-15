<?php

namespace App\Core\Security\Strategy;

use Symfony\Component\HttpFoundation\Request;

class JwtStrategy implements StrategyInterface
{

    public function __construct()
    {
    }

    public function authenticate(Request $request): bool
    {
        return true;
    }
}