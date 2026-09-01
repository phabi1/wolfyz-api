<?php

namespace App\HelloAsso\Webhook\Handler;

use App\Core\UseCase\UseCaseBus;

class ReceivePaymentHandler implements WebhookHandlerInterface
{
    private $useCaseBus;

    public function __construct(
        UseCaseBus $useCaseBus
    ) {
        $this->useCaseBus = $useCaseBus;
    }

    public function handle(array $payload = []): void
    {
        if ($payload['data']['state'] !== 'Authorized') {
            return;
        }

        $data = $payload['data'] ?? [];

        $externalId = $payload['metadata']['external_id'] ?? '';
        $orderId = $data['order']['id'] ?? '';
        $amount = $data['amount'] ?? 0;
        $payedAt = !empty($data['date']) ? strtotime($data['date']) : time();
        $installmentNumber = $data['installmentNumber'] ?? 0;

        $this->addPayment($amount, $payedAt, [
            'firstname' => $data['payer']['firstName'] ?? '',
            'lastname' => $data['payer']['lastName'] ?? '',
            'email' => $data['payer']['email'] ?? '',
        ], $externalId, [
            'provider' => 'helloasso',
            'order_id' => $orderId,
            'installment_number' => $installmentNumber,
        ]);
    }

    private function addPayment(int $amount, int $payedAt, array $payer, string $externalId, array $meta = []): void
    {
        $this->useCaseBus->execute('wolf-billing.add_payment', [
            'external_id' => $externalId,
            'type' => 'credit',
            'payment_method' => 'credit_card',
            'payer' => $payer,
            'amount' => $amount,
            'currency' => 'EUR',
            'payed_at' => $payedAt,
            'meta' => $meta
        ]);
    }
}