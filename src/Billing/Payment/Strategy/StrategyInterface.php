<?php

namespace App\Billing\Payment\Strategy;

interface StrategyInterface
{
    public function payment(array $params);

    public function callback(array $params);
}