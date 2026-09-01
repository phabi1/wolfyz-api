<?php

namespace App\HelloAsso\Sdk;

use App\HelloAsso\Sdk\Model\Order;

class Checkout
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function createPaymentIntent(Order $order, $organizationSlug = null)
    {
        $organizationSlug = $organizationSlug ?? $this->client->getOrganizationSlug();

        $base_args = [];

        $extrenalId = $order->metadata['external_id'] ?? null;
        if ($extrenalId) {
            $base_args['external_id'] = $extrenalId;
        }

        $defaultReturnUrl = $order->return_url;

        if (!$defaultReturnUrl) {
            throw new \InvalidArgumentException('Return URL is required in the Order data.');
        }

        // Build back url based on the current request if not provided
        $returnUrl = str_replace('http:', 'https:', add_query_arg(array_merge($base_args, ['type' => 'success']), $defaultReturnUrl));
        if (!$order->back_url) {
            $backUrl = str_replace('http:', 'https:', add_query_arg(array_merge($base_args, ['type' => 'back']), $defaultReturnUrl));
        } else {
            $backUrl = str_replace('http:', 'https:', add_query_arg($base_args, $order->back_url));
        }
        if (!$order->error_url) {
            $errorUrl = str_replace('http:', 'https:', add_query_arg(array_merge($base_args, ['type' => 'error']), $defaultReturnUrl));
        } else {
            $errorUrl = str_replace('http:', 'https:', add_query_arg($base_args, $order->error_url));
        }
        
        $payload = [
            'totalAmount' => (int) $order->amount_cents, // en centimes
            'itemName' => $order->item_name,
            'backUrl' => $backUrl,
            'errorUrl' => $errorUrl,
            'returnUrl' => $returnUrl,
            'containsDonation' => false,
            'payer' => [
                'firstName' => $order->payer->first_name,
                'lastName' => $order->payer->last_name,
                'email' => $order->payer->email,
            ],
            'metadata' => $order->metadata
        ];


        if (empty($order->terms)) {
            $payload['initialAmount'] = (int) $order->amount_cents;
        } else {
            $terms = $order->terms;
            $payload['initialAmount'] = (int) $terms[0]['amount'] ?? 0;
            // Remove first term from the list of terms to avoid duplication
            array_shift($terms);
            $payload['terms'] = array_map(function ($term) {
                return [
                    'amount' => (int) $term['amount'] ?? 0,
                    'date' => date('Y-m-d H:i:s', $term['date']),
                ];
            }, $terms);
        }

        return $this->client->request('POST', 'organizations/' . $organizationSlug . '/checkout-intents', $payload);
    }
}