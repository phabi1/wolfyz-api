<?php

namespace App\Membership\Controller;

use App\Core\Mvc\Controller\ApiController;
use App\File\Presign\PresignedUrlService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileController extends ApiController
{
    public function uploadAction(Request $request)
    {
        $payload = $request->getPayload()->all();

        if (empty($payload['file'])) {
            return new JsonResponse(['message' => 'No file URL uploaded'], 400);
        }

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');
        $uri = $presignedUrlService->upload('membership/' . $payload['file'], $payload['mime_type']);
        return [
            'success' => true,
            'url' => $uri,
        ];
    }

    public function removeAction(Request $request)
    {
        $payload = $request->getPayload()->all();
        $file = $payload['file'] ?? null;

        if (!$file) {
            return new JsonResponse(['message' => 'No file URL provided'], 400);
        }

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');
        $uri = $presignedUrlService->remove($file);

        return [
            'success' => true,
            'url' => $uri,
        ];
    }

    public function downloadAction(Request $request)
    {
        $payload = $request->getPayload()->all();
        $file = $payload['file'] ?? null;

        if (!$file) {
            return new JsonResponse(['message' => 'No file URL provided'], 400);
        }

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');
        $uri = $presignedUrlService->download($file);

        return [
            'success' => true,
            'url' => $uri,
        ];
    }
}