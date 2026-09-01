<?php

namespace App\Membership\Controller;

class RequestController extends AbstractCampaignController
{

    protected $entityName = 'wolf-memberships.request';

    public function approveAction(\WP_REST_Request $request)
    {
        if (!$request->get_param('campaign_id') || !$request->get_param('request_id')) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $user = wp_get_current_user();

        $useCaseBus = $this->getService('wolf.use_case_bus');
        $useCaseBus->execute('wolf-memberships.approve_request', [
            'campaign_id' => $request->get_param('campaign_id'),
            'request_id' => $request->get_param('request_id'),
            'user_id' => $user->ID
        ]);

        return [
            'success' => true,
            'message' => 'Request approved successfully.'
        ];
    }

    public function rejectAction(\WP_REST_Request $request)
    {
        if (!$request->get_param('campaign_id') || !$request->get_param('request_id')) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $user = wp_get_current_user();

        $useCaseBus = $this->getService('wolf.use_case_bus');
        $useCaseBus->execute('wolf-memberships.reject_request', [
            'campaign_id' => $request->get_param('campaign_id'),
            'request_id' => $request->get_param('request_id'),
            'user_id' => $user->ID,
            'reason' => $request->get_param('reason') ?? ''
        ]);

        return [
            'success' => true,
            'message' => 'Request rejected successfully.'
        ];
    }

    public function cancelAction(\WP_REST_Request $request)
    {
        if (!$request->get_param('campaign_id') || !$request->get_param('request_id')) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $user = wp_get_current_user();

        $useCaseBus = $this->getService('wolf.use_case_bus');
        $useCaseBus->execute('wolf-memberships.cancel_request', [
            'campaign_id' => $request->get_param('campaign_id'),
            'request_id' => $request->get_param('request_id'),
            'user_id' => $user->ID
        ]);

        return [
            'success' => true,
            'message' => 'Request canceled successfully.'
        ];
    }

    public function paidAction(\WP_REST_Request $request)
    {
        if (!$request->get_param('campaign_id') || !$request->get_param('request_id')) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $user = wp_get_current_user();

        $useCaseBus = $this->getService('wolf.use_case_bus');
        $useCaseBus->execute('wolf-memberships.paid_request', [
            'campaign_id' => $request->get_param('campaign_id'),
            'request_id' => $request->get_param('request_id'),
            'user_id' => $user->ID
        ]);

        return [
            'success' => true,
            'message' => 'Request marked as paid successfully.'
        ];
    }

    public function historyAction(\WP_REST_Request $request)
    {
        $requestId = $request->get_param('request_id');
        if (!$requestId) {
            return [
                'success' => false,
                'message' => 'Missing request_id parameter.'
            ];
        }

        $useCaseBus = $this->getService('wolf.use_case_bus');
        $history = $useCaseBus->execute('wolf-memberships.get_history_of_request', [
            'request_id' => $requestId
        ]);

        return [
            'success' => true,
            'data' => $history
        ];
    }

    public function resendPaymentAction(\WP_REST_Request $request)
    {
        if (!$request->get_param('campaign_id') || !$request->get_param('request_id')) {
            return [
                'success' => false,
                'message' => 'Missing campaign_id or request_id parameter.'
            ];
        }

        $useCaseBus = $this->getService('wolf.use_case_bus');
        $useCaseBus->execute('wolf-memberships.resend_payment', [
            'campaign_id' => $request->get_param('campaign_id'),
            'request_id' => $request->get_param('request_id'),
        ]);

        return [
            'success' => true,
            'message' => 'Payment resent successfully.'
        ];
    }
}