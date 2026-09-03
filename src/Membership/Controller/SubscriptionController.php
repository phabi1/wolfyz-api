<?php

namespace App\Membership\Controller;

use App\Core\UseCase\UseCaseBus;
use Symfony\Component\HttpFoundation\JsonResponse;

class SubscriptionController extends AbstractCampaignController
{

    protected $entityName = 'wolf-memberships.subscription';

    public function importAction($request)
    {
        $campaignId = $request->get_param('campaign_id');
        if (!$campaignId) {
            return new JsonResponse(['error' => 'campaign_id_required', 'message' => 'Campaign ID parameter is required'], 400);
        }
        $files = $request->get_file_params();

        if (empty($files['file'])) {
            return new JsonResponse(['error' => 'file_not_provided', 'message' => 'No file provided for import'], 400);
        }

        $log = $this->useCaseBus('wolf-memberships.import_subscriptions', [
            'campaign_id' => $campaignId,
            'file' => $files['file']['tmp_name']
        ]);

        return [
            'success' => true,
            'log' => $log
        ];
    }

    public function exportAction($request)
    {
        $campaignId = $request->get_param('campaign_id');
        if (!$campaignId) {
            return new JsonResponse(['error' => 'campaign_id_required', 'message' => 'Campaign ID parameter is required'], 400);
        }

        $log = $this->useCaseBus('wolf-memberships.export_subscriptions', [
            'campaign_id' => $campaignId
        ]);

        if (isset($log['error'])) {
            return new JsonResponse(['error' => 'export_failed', 'message' => $log['error']], 500);
        }

        // Serve the file for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="subscriptions_export.csv"');
        readfile($log['file_url']);
        unlink($log['file_url']); // Clean up the temporary file
        exit;
    }

    protected function buildSearchFilters($search, &$filters)
    {
        if ($search) {
            $filters['member.lastname'] = ['like' => '%' . $search . '%'];
        }
    }
}