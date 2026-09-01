<?php

namespace App\Billing\UseCase;

use App\Core\UseCase\UseCaseInterface;
use App\Core\Entity\EntityManager;

class AddPaymentUseCase implements UseCaseInterface
{
    private $paymentRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->paymentRepository = $entityManager->getRepository('wolf-billing.payment');
    }

    public function execute(array $params = [])
    {
        $payer = $params['payer'] ?? [
            'firstname' => null,
            'lastname' => null,
            'email' => null,
        ];

        $data = [
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'type' => $params['type'] ?? 'credit',
            'payment_method' => $params['payment_method'],
            'payer_firstname' => $payer['firstname'],
            'payer_lastname' => $payer['lastname'],
            'payer_email' => $payer['email'],
            'payed_at' => $params['payed_at'],
            'external_id' => $params['external_id'],
            'created_at' => time(),
            'updated_at' => time(),
            'meta' => $params['meta'] ?? null,
        ];

        $item = $this->paymentRepository->insert($data);

        return $item;
    }
}