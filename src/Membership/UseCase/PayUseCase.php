<?php

namespace App\Membership\UseCase;

use App\Core\UseCase\UseCaseInterface;
use App\Core\UseCase\UseCaseBus;

class PayUseCase implements UseCaseInterface
{
    private UseCaseBus $useCaseBus;

    public function __construct(UseCaseBus $useCaseBus)
    {
        $this->useCaseBus = $useCaseBus;
    }

    public function execute(array $params = [])
    {
        $paymentMethodId = $params['payment_method'] ?? null;

        $campaign = $params['campaign'];
        $request = $params['request'];

        $externalId = 'membership:' . $campaign->id . ':' . $request->id;

        $paymentMethod = null;
        if (isset($campaign->settings->payment_methods)) {
            foreach ($campaign->settings->payment_methods as $method) {
                if ($method->id === $paymentMethodId) {
                    $paymentMethod = $method;
                    break;
                }
            }
        }

        if ($paymentMethod === null) {
            throw new \InvalidArgumentException('Invalid payment method.');
        }

        switch ($paymentMethod->type) {
            case 'credit_card':
                return $this->processCreditCardPayment((int) $params['pay']['total_amount'], $campaign, $request, $externalId);
            case 'credit_card_x':
                return $this->processCreditCardXPayment($params['pay'], $paymentMethod->options, $campaign, $request, $externalId);
            case 'bank_transfer':
                return $this->processBankTransferPayment((int) $params['pay']['total_amount'], $campaign, $request, $externalId);
            case 'check':
                return $this->processCheckPayment((int) $params['pay']['total_amount'], $campaign, $request, $externalId);
            default:
                throw new \InvalidArgumentException('Invalid type of payment method.');
        }
    }

    /**
     * Process credit card payment
     *
     * @param int $amount
     * @param \stdClass $campaign
     * @param \stdClass $request
     * @param string $externalId
     * @return array
     */
    private function processCreditCardPayment(int $amount, \stdClass $campaign, \stdClass $request, string $externalId)
    {
        return $this->useCaseBus->execute('wolf-billing.create_payment', [
            'amount' => $amount,
            'currency' => 'EUR',
            'payment_method' => 'multiplehelloasso',
            'name' => $this->buildTitle($campaign),
            'payer' => $this->buildPayer($request),
            'metadata' => ['external_id' => $externalId],
            'return_url' => $this->buildResultUrl()
        ]);
    }

    /**
     * Process credit card x payment
     *
     * @param array $pay
     * @param \stdClass $campaign
     * @param \stdClass $request
     * @param string $externalId
     * @return array
     */
    private function processCreditCardXPayment(array $pay, \stdClass $options, \stdClass $campaign, \stdClass $request, string $externalId)
    {
        $amount = (int) $pay['total_amount'];
        $periods = $options->periods ?? [];
        $nbPeriods = count($periods);

        $fees = 0;
        $amount = 0;
        foreach ($pay['items'] as $item) {
            if ($item['type'] === 'fee') {
                $fees += (int) $item['amount'];
            } else {
                $amount += (int) $item['amount'];
            }
        }

        $baseAmount = floor($amount / $nbPeriods);

        $terms = [];
        $terms[] = [
            'amount' => $baseAmount + $fees,
            'date' => time(),
        ];
        $amount -= $baseAmount;

        // Remove the first period as it has already been accounted for in the initial term
        array_shift($periods);

        foreach ($periods as $period) {
            $terms[] = [
                'amount' => $baseAmount,
                'date' => strtotime($period),
            ];
            $amount -= $baseAmount;
        }

        // Regularize the last term to account for any rounding differences
        if ($amount > 0) {
            $terms[count($terms) - 1]['amount'] += $amount;
        }

        return $this->useCaseBus->execute('wolf-billing.create_payment', [
            'amount' => $pay['total_amount'],
            'currency' => 'EUR',
            'payment_method' => 'multiplehelloasso',
            'name' => $this->buildTitle($campaign),
            'payer' => $this->buildPayer($request),
            'items' => $terms,
            'metadata' => ['external_id' => $externalId],
            'return_url' => $this->buildResultUrl()
        ]);
    }

    /**
     * Process bank transfer payment
     *
     * @param int $amount
     * @param \stdClass $campaign
     * @param \stdClass $request
     * @param string $externalId
     * @return array
     */
    private function processBankTransferPayment(int $amount, \stdClass $campaign, \stdClass $request, string $externalId)
    {
        return $this->useCaseBus->execute('wolf-billing.create_payment', [
            'amount' => $amount,
            'currency' => 'EUR',
            'payment_method' => 'bank_transfer',
            'name' => $this->buildTitle($campaign),
            'payer' => $this->buildPayer($request),
            'metadata' => ['external_id' => $externalId],
            'return_url' => $this->buildResultUrl()
        ]);
    }

    /**
     * Process check payment
     *
     * @param int $amount
     * @param \stdClass $campaign
     * @param \stdClass $request
     * @param string $externalId
     * @return array
     */
    private function processCheckPayment(int $amount, \stdClass $campaign, \stdClass $request, string $externalId)
    {
        return $this->useCaseBus->execute('wolf-billing.create_payment', [
            'amount' => $amount,
            'currency' => 'EUR',
            'payment_method' => 'check',
            'name' => $this->buildTitle($campaign),
            'payer' => $this->buildPayer($request),
            'metadata' => ['external_id' => $externalId],
            'return_url' => $this->buildResultUrl()
        ]);
    }

    /**
     * Build payer
     * @param \stdClass $request
     * @return array{email: mixed, first_name: mixed, last_name: mixed}
     */
    private function buildPayer(\stdClass $request): array
    {
        return [
            'first_name' => $request->firstname,
            'last_name' => $request->lastname,
            'email' => $request->email
        ];
    }

    /**
     * Build title for the payment
     * @param \stdClass $campaign
     * @return string
     */
    private function buildTitle(\stdClass $campaign): string
    {
        return 'Inscription à la campagne "' . $campaign->title . '"';
    }

    /**
     * Build the result URL for the payment
     * @return string Return URL for the payment result
     */
    private function buildResultUrl(): string
    {
        $pageId = (int) get_option('wolf_membership_result_page', 0);
        return get_permalink($pageId);
    }
}