<?php

namespace App\Membership\Controller;

use App\Core\Mvc\Controller\ApiController;
use App\File\Presign\PresignedUrlService;
use App\File\Service\FileService;
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

        if (empty($payload['mime_type'])) {
            return new JsonResponse(['message' => 'MIME type is required'], 400);
        }

        /**
         * @var FileService
         */
        $fileService = $this->getService('file');
        $path = $fileService->resolveAvailablePath('membership', (string) $payload['file']);

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');
        $uri = $presignedUrlService->upload($path, $payload['mime_type']);
        return [
            'success' => true,
            'url' => $uri,
            'path' => $path,
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