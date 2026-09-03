<?php

namespace App\Membership\Controller;

use Symfony\Component\HttpFoundation\Request;

class RequestController extends AbstractCampaignController
{

    protected $entityName = 'wolf-memberships.request';

    public function approveAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        $requestId = $request->attributes->get('request_id');
        if (!$campaignId || !$requestId) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $identity = $this->identity();

        $this->useCaseBus('wolf-memberships.approve_request', [
            'campaign_id' => $campaignId,
            'request_id' => $requestId,
            'user_id' => $identity->getId()
        ]);

        return [
            'success' => true,
            'message' => 'Request approved successfully.'
        ];
    }

    public function rejectAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        $requestId = $request->attributes->get('request_id');
        if (!$campaignId || !$requestId) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $identity = $this->identity();

        $payload = $request->getPayload()->all();

        $this->useCaseBus('wolf-memberships.reject_request', [
            'campaign_id' => $campaignId,
            'request_id' => $requestId,
            'user_id' => $identity->getId(),
            'reason' => $payload['reason'] ?? ''
        ]);

        return [
            'success' => true,
            'message' => 'Request rejected successfully.'
        ];
    }

    public function cancelAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        $requestId = $request->attributes->get('request_id');
        if (!$campaignId || !$requestId) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $identity = $this->identity();

        $this->useCaseBus('wolf-memberships.cancel_request', [
            'campaign_id' => $campaignId,
            'request_id' => $requestId,
            'user_id' => $identity->getId()
        ]);

        return [
            'success' => true,
            'message' => 'Request canceled successfully.'
        ];
    }

    public function paidAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        $requestId = $request->attributes->get('request_id');
        if (!$campaignId || !$requestId) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $identity = $this->identity();

        $this->useCaseBus('wolf-memberships.paid_request', [
            'campaign_id' => $campaignId,
            'request_id' => $requestId,
            'user_id' => $identity->getId()
        ]);

        return [
            'success' => true,
            'message' => 'Request marked as paid successfully.'
        ];
    }

    public function historyAction(Request $request)
    {
        $requestId = $request->attributes->get('request_id');
        if (!$requestId) {
            return [
                'success' => false,
                'message' => 'Missing request_id parameter.'
            ];
        }

        $history = $this->useCaseBus('wolf-memberships.get_history_of_request', [
            'request_id' => $requestId
        ]);

        return [
            'success' => true,
            'items' => $history
        ];
    }

    public function resendPaymentAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        $requestId = $request->attributes->get('request_id');
        if (!$campaignId || !$requestId) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $this->useCaseBus('wolf-memberships.resend_payment', [
            'campaign_id' => $campaignId,
            'request_id' => $requestId,
        ]);

        return [
            'success' => true,
            'message' => 'Payment resent successfully.'
        ];
    }
}