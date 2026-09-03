<?php

namespace App\Core\Security;

use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;
use App\Core\Di\Locator;
use Symfony\Component\HttpFoundation\Request;

class Firewall implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private ?Locator $locator;

    private $defaultStrategy = 'api-key';

    public function getLocator(): Locator
    {
        if ($this->locator === null) {
            $this->locator = new Locator('firewall.strategy');
            $this->locator->setContainer($this->container);
        }
        return $this->locator;
    }

    public function authenticate(Request $request)
    {
        $auth = $request->attributes->get('auth');
        if ($auth === null) {
            $auth = $this->defaultStrategy;
        }

        $strategy = $this->getLocator()->get($auth);
        return $strategy->authenticate($request);
    }
    
}