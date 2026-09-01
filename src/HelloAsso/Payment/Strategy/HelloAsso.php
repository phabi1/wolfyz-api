<?php

namespace App\HelloAsso\Payment\Strategy;

use App\Billing\Payment\Strategy\StrategyInterface;
use App\HelloAsso\Sdk\Model\Order;

class HelloAsso implements StrategyInterface
{
    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function payment(array $params)
    {
        $payload = new Order();
        $payload->amount_cents = $params['amount'];
        $payload->item_name = $params['name'];
        $payload->payer->first_name = $params['payer']['first_name'];
        $payload->payer->last_name = $params['payer']['last_name'];
        $payload->payer->email = $params['payer']['email'];
        $payload->metadata = $params['metadata'] ?? [];
        $payload->return_url = $params['return_url'] ?? null;
        $payload->back_url = $params['back_url'] ?? null;
        $payload->error_url = $params['error_url'] ?? null;

        $intent = $this->client->getCheckout()->createPaymentIntent($payload);

        return [
            'redirect_url' => $intent['redirectUrl'],
        ];
    }

    public function callback(array $params)
    {
        // Implement callback logic for HelloAsso payment here
    }
}