<?php

namespace App\File\Controller;

use App\Core\Mvc\Controller\AbstractController;
use App\File\Presign\PresignedUrlService;
use App\File\Service\FileService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FileController extends AbstractController
{
    public function uploadAction(Request $request)
    {
        $file = $request->query->get('file');
        $expires = $request->query->get('expires');
        $mimeType = $request->headers->get('Content-Type');
        $signature = $request->query->get('signature');

        if (empty($file) || empty($expires) || empty($mimeType) || empty($signature)) {
            return new JsonResponse(['message' => 'Invalid request'], 400);
        }

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');

        $payload = [
            'file' => $file,
            'expires' => $expires,
            'mime_type' => $mimeType
        ];
        $result = $presignedUrlService->verify($payload, $signature);

        if (!$result->isValid()) {
            return new JsonResponse(['error' => $result->getError()], 400);
        }

        /**
         * @var FileService
         */
        $fileService = $this->getService('file');

        $fileService->prepareDirectory(dirname($file));

        $targetPath = $fileService->getPath($file);

        $inputStream = fopen('php://input', 'rb');
        $outputStream = fopen($targetPath, 'wb');

        if (!$inputStream || !$outputStream) {
            return new JsonResponse(['message' => 'Impossible to write file on server'], 500);
        }

        stream_copy_to_stream($inputStream, $outputStream);

        fclose($inputStream);
        fclose($outputStream);

        $response = new JsonResponse(['success' => true, 'uri' => $file]);
        return $response;
    }

    public function removeAction(Request $request)
    {
        $file = $request->query->get('file');
        $expires = $request->query->get('expires');
        $signature = $request->query->get('signature');

        if (empty($file) || empty($expires) || empty($signature)) {
            return new JsonResponse(['message' => 'Invalid request'], 400);
        }

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');

        $payload = [
            'file' => $file,
            'expires' => $expires,
        ];
        $result = $presignedUrlService->verify($payload, $signature);

        if (!$result->isValid()) {
            return new JsonResponse(['error' => $result->getError()], 400);
        }

        /**
         * @var FileService
         */
        $fileService = $this->getService('file');

        if (!$fileService->fileExists($file)) {
            return new Response('File not found', 404);
        }
        $fileService->unlink($file);

        return new JsonResponse(['success' => true]);
    }

    public function downloadAction(Request $request)
    {
        $file = $request->query->get('file');
        $expires = $request->query->get('expires');
        $signature = $request->query->get('signature');

        if (empty($file) || empty($expires) || empty($signature)) {
            return new JsonResponse(['message' => 'Invalid request'], 400);
        }

        /**
         * @var PresignedUrlService
         */
        $presignedUrlService = $this->getService('file.presigned-url');

        $payload = [
            'file' => $file,
            'expires' => $expires,
        ];
        $result = $presignedUrlService->verify($payload, $signature);

        if (!$result->isValid()) {
            return new JsonResponse(['error' => $result->getError()], 400);
        }

        /**
         * @var FileService
         */
        $fileService = $this->getService('file');

        if (!$fileService->fileExists($file)) {
            return new Response('File not found', 404);
        }
        $targetPath = $fileService->getPath($file);

        $contentType = mime_content_type($targetPath); 

        return new BinaryFileResponse($targetPath, 200, ['Content-Type' => $contentType], true);
    }
}