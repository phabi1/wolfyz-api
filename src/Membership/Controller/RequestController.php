<?php

namespace App\Membership\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

    public function invoiceAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        $requestId = $request->attributes->get('request_id');
        if (!$campaignId || !$requestId) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $invoice = $this->useCaseBus('wolf-memberships.create_invoice_from_request', [
            'campaign_id' => (int) $campaignId,
            'request_id' => (int) $requestId,
            'user_id' => $this->identity()?->getId(),
        ]);

        return [
            'success' => true,
            'item' => $invoice,
        ];
    }

    public function sendInvoiceEmailAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        $requestId = $request->attributes->get('request_id');
        if (!$campaignId || !$requestId) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $this->useCaseBus('wolf-memberships.send_invoice_email_from_request', [
            'campaign_id' => (int) $campaignId,
            'request_id' => (int) $requestId,
            'user_id' => $this->identity()?->getId(),
        ]);

        return [
            'success' => true,
            'message' => 'Invoice email sent successfully.',
        ];
    }

    public function downloadInvoiceAction(Request $request)
    {
        $campaignId = (int) $request->attributes->get('campaign_id');
        $requestId = (int) $request->attributes->get('request_id');
        $token = (string) $request->query->get('token', '');

        $result = $this->useCaseBus('wolf-memberships.download_invoice_from_request', [
            'campaign_id' => $campaignId,
            'request_id' => $requestId,
            'token' => $token,
        ]);

        return new Response(
            $result['content'],
            200,
            [
                'Content-Type' => $result['mime_type'] ?? 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . ($result['filename'] ?? 'facture.pdf') . '"',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]
        );
    }
}