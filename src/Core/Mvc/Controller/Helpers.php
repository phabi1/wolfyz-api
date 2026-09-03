<?php

namespace App\Core\Mvc\Controller;

use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;
use App\Core\Di\Locator;

class Helpers implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private ?Locator $locator = null;


    public function getLocator()
    {
        if ($this->locator === null) {
            $this->locator = new Locator('controller.helper');
            $this->locator->setContainer($this->container);
        }
        return $this->locator;
    }

    public function has(string $type)
    {
        return $this->getLocator()->has($type);
    }

    public function get(string $type)
    {
        return $this->getLocator()->get($type);
    }
}