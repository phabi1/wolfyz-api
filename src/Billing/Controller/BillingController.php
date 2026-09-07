<?php

namespace App\Billing\Controller;

use App\Core\Mvc\Controller\ApiController;

class BillingController extends ApiController
{
    public function bankAmountAction()
    {
        $amount = $this->useCaseBus('wolf-billing.get_bank_amount', []);
        return ['amount' => $amount];
    }
}