<?php

namespace App\Billing\UseCase;

use App\Billing\Payment\PaymentManager;
use App\Core\UseCase\UseCaseInterface;

class CreatePaymentUseCase implements UseCaseInterface
{
    private PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function execute(array $params = [])
    {
        $payment = $this->paymentManager->resolve($params['payment_method']);

        $response = $payment->payment($params);

        return $response;
    }
}