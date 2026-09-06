<?php

namespace App\Core\Security;

use App\Core\Di\Container;
use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;
use App\Core\Di\Locator;
use Symfony\Component\HttpFoundation\Request;

class Firewall implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private Locator $locator;

    private $defaultStrategy = 'api-key';

    public function __construct()
    {
        $this->locator = new Locator('security.strategy');
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
        $this->locator->setContainer($container);
    }

    public function authenticate(Request $request)
    {
        $auth = $request->attributes->get('auth');
        if ($auth === null) {
            $auth = $this->defaultStrategy;
        }

        $strategy = $this->locator->get($auth);
        return $strategy->authenticate($request);
    }
    
}