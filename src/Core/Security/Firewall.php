<?php

namespace App\Core\Security;

use App\Core\Di\Locator;
use Symfony\Component\HttpFoundation\Request;

class Firewall
{
    private Locator $strategies;

    private $defaultStrategy = 'api-key';

    public function __construct(Locator $strategies, $defaultStrategy = 'api-key')
    {
        $this->strategies = $strategies;
        $this->defaultStrategy = $defaultStrategy;
    }

    public function authenticate(Request $request)
    {
        $auth = $request->attributes->get('auth');

        if ($auth === null) {
            $auth = $this->defaultStrategy;
        }

        $strategy = $this->strategies->get($auth);
        return $strategy->authenticate($request);
    }
    
}