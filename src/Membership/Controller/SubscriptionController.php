<?php

namespace App\Membership\Controller;

use App\Core\UseCase\UseCaseBus;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SubscriptionController extends AbstractCampaignController
{

    protected $entityName = 'wolf-memberships.subscription';

    protected function buildEntityService()
    {
        return $this->getService('wolf-memberships.entity.service.subscription');
    }

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

        $filters = $request->query->get('filters');
        $reverse = $request->query->get('reverse');
        $ids = $request->query->has('ids') ? explode(',', $request->query->get('ids')) : [];

        $params = [
            'campaign_id' => $campaignId,
            'filters' => $filters ?? [],
            'reverse' => $reverse ?? false,
            'ids' => $ids ?? []
        ];

        if ($request->query->has('format')) {
            $params['format'] = $request->query->get('format');
        }

        $res = $this->useCaseBus('wolf-memberships.export_subscriptions', $params);

        if (isset($res['error'])) {
            return new JsonResponse(['error' => 'export_failed', 'message' => $res['error']], 500);
        }

        $contentType = match ($res['format']) {
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => throw new \UnexpectedValueException('Unsupported export format: ' . $res['format']),
        };

        $response = new BinaryFileResponse($res['file_url'], 200, ['Content-Type' => $contentType], false);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'subscriptions_export.' . $res['format']);
        $response->deleteFileAfterSend();

        return $response;
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

    public function licenseTakenAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        if (!$campaignId) {
            return new JsonResponse(['error' => 'campaign_id_required', 'message' => 'Campaign ID parameter is required'], 400);
        }

        $body = $request->getPayload()->all();

        $takenAt = $body['taken_at'] ?? null;
        if ($takenAt !== null && !is_int($takenAt)) {
            return new JsonResponse(['error' => 'invalid_taken_at', 'message' => 'taken_at must be a timestamp or null'], 400);
        }

        return $this->useCaseBus('wolf-memberships.take_license', [
            'campaign_id' => (int) $campaignId,
            'ids' => $body['ids'] ?? [],
            'reverse' => (bool) ($body['reverse'] ?? false),
            'taken_at' => $takenAt,
        ]);
    }

    public function countByGenderAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        if (!$campaignId) {
            return new JsonResponse(['error' => 'campaign_id_required', 'message' => 'Campaign ID parameter is required'], 400);
        }

        $repository = $this->getService('entity.manager')->getRepository('wolf-memberships.subscription');
        $countByGender = $repository->countByGender($campaignId);

        return new JsonResponse($countByGender);
    }

    public function countByAgeAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        if (!$campaignId) {
            return new JsonResponse(['error' => 'campaign_id_required', 'message' => 'Campaign ID parameter is required'], 400);
        }

        $entityManager = $this->getService('entity.manager');
        $campaign = $entityManager->getRepository('wolf-memberships.campaign')->findById($campaignId);
        if (!$campaign) {
            return new JsonResponse(['error' => 'campaign_not_found', 'message' => 'Campaign not found'], 404);
        }

        // Ages are computed at the start of the campaign (season), or today without a start date
        $referenceDate = $campaign->start_date
            ? (new \DateTimeImmutable())->setTimestamp((int) $campaign->start_date)
            : new \DateTimeImmutable();

        $result = $entityManager->getRepository('wolf-memberships.subscription')->countByAge($campaignId, $referenceDate);

        return new JsonResponse([
            'reference_date' => $referenceDate->format('Y-m-d'),
            'ages' => (object) $result['ages'],
            'unknown' => $result['unknown'],
        ]);
    }

    public function countByLicenseTypeAction(Request $request)
    {
        $campaignId = $request->attributes->get('campaign_id');
        if (!$campaignId) {
            return new JsonResponse(['error' => 'campaign_id_required', 'message' => 'Campaign ID parameter is required'], 400);
        }

        $licenseTypes = $request->query->get('license_types', []);

        $repository = $this->getService('entity.manager')->getRepository('wolf-memberships.subscription');
        $countByLicenseType = $repository->countByLicenseType($campaignId, $licenseTypes);

        return new JsonResponse($countByLicenseType);
    }
}