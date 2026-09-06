<?php

namespace App\Membership\Controller;

use App\Core\UseCase\UseCaseBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SubscriptionController extends AbstractCampaignController
{

    protected $entityName = 'wolf-memberships.subscription';

    public function importAction($request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        if (!$campaignId) {
            return new JsonResponse(['error' => 'campaign_id_required', 'message' => 'Campaign ID parameter is required'], 400);
        }
        $files = $request->files->all();

        if (empty($files['file'])) {
            return new JsonResponse(['error' => 'file_not_provided', 'message' => 'No file provided for import'], 400);
        }

        $filepath = APP_DIR . '/uploads/' . uniqid() . '.csv';
        if (!is_dir(APP_DIR . '/uploads/')) {
            mkdir(APP_DIR . '/uploads/', 0777, true);
        }
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
            return new JsonResponse(['error' => 'file_upload_failed', 'message' => 'Failed to upload the file'], 500);
        }

        $log = $this->useCaseBus('wolf-memberships.import_subscriptions', [
            'campaign_id' => $campaignId,
            'file' => $filepath
        ]);

        return [
            'success' => true,
            'log' => $log
        ];
    }

    public function exportAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
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

    public function syncAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        if (!$campaignId) {
            return new JsonResponse(['error' => 'campaign_id_required', 'message' => 'Campaign ID parameter is required'], 400);
        }

        $files = $request->files->all();

        if (empty($files['file'])) {
            return new JsonResponse(['error' => 'file_not_provided', 'message' => 'No file provided for sync'], 400);
        }

        $filepath = APP_DIR . '/uploads/' . uniqid() . '.csv';
        if (!is_dir(APP_DIR . '/uploads/')) {
            mkdir(APP_DIR . '/uploads/', 0777, true);
        }
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
            return new JsonResponse(['error' => 'file_upload_failed', 'message' => 'Failed to upload the file'], 500);
        }

        $log = $this->useCaseBus('wolf-memberships.sync_subscriptions', [
            'campaign_id' => $campaignId,
            'file' => $filepath
        ]);

        return [
            'success' => true,
            'log' => $log
        ];
    }
}