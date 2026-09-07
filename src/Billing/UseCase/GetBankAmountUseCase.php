<?php

namespace App\Billing\UseCase;

use App\Billing\Entity\Repository\PaymentRepositoryInterface;
use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;

class GetBankAmountUseCase implements UseCaseInterface
{
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(EntityManager $entityManager)
    {
        $paymentRepository = $entityManager->getRepository('wolf-billing.payment');
        if ($paymentRepository instanceof PaymentRepositoryInterface === false) {
            throw new \RuntimeException('Payment repository does not implement PaymentRepositoryInterface');
        }
        $this->paymentRepository = $paymentRepository;
    }

    public function execute(array $params = [])
    {
        $amount = $this->paymentRepository->amount();

        return $amount ?? 0; // Replace with the actual amount
    }
}