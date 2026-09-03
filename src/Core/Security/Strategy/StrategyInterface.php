<?php

namespace App\Core\Security\Strategy;

use Symfony\Component\HttpFoundation\Request;

interface StrategyInterface
{
    public function authenticate(Request $request): bool;
}