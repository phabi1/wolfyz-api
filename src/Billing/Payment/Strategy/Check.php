<?php

namespace App\Billing\Payment\Strategy;

use App\Billing\Payment\Strategy\StrategyInterface;
use App\Core\Config\Settings;

class Check implements StrategyInterface
{
    private $settings;

    function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }
    public function payment(array $params)
    {
        $amount = $params['amount'] ?? 0;
        $reference = $params['reference'] ?? '';

        $paymentUrl = $this->settings->get('wolf_billing_check_instruction_page');

        // Append the amount and reference as query parameters to the payment URL
        $paymentUrl = $this->addQueryArgs([
            'amount' => $amount,
            'reference' => $reference,
        ], $paymentUrl);

        return [
            'redirect_url' => $paymentUrl,
        ];
    }

    public function callback(array $params)
    {
    }

    // Implement callback logic for cheque payment here
    private function addQueryArgs(array $args, string $url): string
    {
        $query = http_build_query($args);
        return $url . (strpos($url, '?') === false ? '?' : '&') . $query;
    }

}