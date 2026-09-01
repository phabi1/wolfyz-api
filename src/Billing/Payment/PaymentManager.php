<?php

namespace App\Billing\Payment;

use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;
use App\Core\Di\Locator;

class PaymentManager implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $locator;

    public function resolve(string $type): Strategy\StrategyInterface
    {
        if (!$this->locator) {
            $this->locator = new Locator('wolf_billing.payment.strategy');
            $this->locator->setContainer($this->container);
        }

        $strategy = $this->locator->get($type);
        if (!$strategy) {
            throw new \Exception("Payment strategy $type not found");
        }

        if (!$strategy instanceof Strategy\StrategyInterface) {
            throw new \Exception("Payment strategy $type does not implement StrategyInterface");
        }
        
        return $strategy;
    }
}