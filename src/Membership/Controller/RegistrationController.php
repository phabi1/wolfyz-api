<?php


namespace App\Membership\Controller;

use App\Core\Mvc\Controller\ApiController;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends ApiController
{
    public function registrationAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');

        $res = $this->useCaseBus('wolf-memberships.get_registration_for_campaign', [
            'campaign_id' => $campaignId,
            'request_id' => $request->query->get('request_id'),
            'token' => $request->query->get('token')
        ]);

        return $res;
    }

    public function calculateTotalAction(Request $request)
    {
        
        $campaignId = $request->attributes->get('campaign_id');
        
        $payload = $request->getPayload()->all();

        $participants = $payload['participants'] ?? [];
        $discount = $payload['discount'] ?? 0;

        $total = $this->useCaseBus('wolf-memberships.calculate_registration_total', [
            'campaign_id' => $campaignId,
            'participants' => $participants,
            'discount_amount' => $discount,
        ]);

        return [
            'success' => true,
            'total_amount' => $total['total_amount'] ?? 0,
            'currency' => $total['currency'] ?? 'EUR',
            'pricing_breakdown' => $total['items'] ?? [],
        ];
    }

    public function registerAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        $payload = $request->getPayload()->all();

        $requestId = $payload['request_id'] ?? null;
        $token = $payload['token'] ?? null;

        if ($requestId) {
            $this->useCaseBus('wolf-memberships.update_request', [
                'campaign_id' => $campaignId,
                'request_id' => $requestId,
                'token' => $token,
                'contact' => [
                    'firstname' => $payload['data']['contact']['firstname'] ?? null,
                    'lastname' => $payload['data']['contact']['lastname'] ?? null,
                    'email' => $payload['data']['contact']['email'] ?? null,
                    'phone' => $payload['data']['contact']['phone'] ?? null,
                ],
                'data' => $payload['data'] ?? [],
            ]);
        } else {
            $this->useCaseBus('wolf-memberships.register_to_campaign', [
                'campaign_id' => $campaignId,
                'contact' => [
                    'firstname' => $payload['data']['contact']['firstname'] ?? null,
                    'lastname' => $payload['data']['contact']['lastname'] ?? null,
                    'email' => $payload['data']['contact']['email'] ?? null,
                    'phone' => $payload['data']['contact']['phone'] ?? null,
                ],
                'data' => $payload['data'] ?? [],
            ]);
        }

        return [
            'success' => true,
        ];
    }
}