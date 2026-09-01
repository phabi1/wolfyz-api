<?php

namespace App\Billing\UseCase;

class GetPaymentsByMetaUseCase
{
    private $paymentRepository;

    public function __construct($paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function execute(array $params = []): array
    {
        if (!isset($params['meta_key']) || !isset($params['meta_value'])) {
            return [];
        }

        $results = $this->paymentRepository->find([
            'meta_key' => ['eq' => $params['meta_key']],
            'meta_value' => ['eq' => $params['meta_value']],
        ]);

        return $results ?: [];
    }
}