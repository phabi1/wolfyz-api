<?php

namespace App\File\Presign;

class PresignedUrlService
{
    private $baseUrl;
    private $secret;

    public function __construct(string $baseUrl, string $secret)
    {
        $this->secret = $secret;
        $this->baseUrl = $baseUrl;
    }

    public function upload(string $file, string $mimeType, int $expiresIn = 900): string
    {
        $query = $this->sign([
            'file' => $file,
            'mime_type' => $mimeType,
        ], $expiresIn);
        return $this->baseUrl . '/file/upload' . '?' . $query;
    }

    public function remove(string $file, int $expiresIn = 900): string
    {
        $query = $this->sign([
            'file' => $file,
        ], $expiresIn);
        return $this->baseUrl . '/file/remove' . '?' . $query;
    }

    public function download(string $file, int $expiresIn = 900): string
    {
        $query = $this->sign([
            'file' => $file,
        ], $expiresIn);
        return $this->baseUrl . '/file/download' . '?' . $query;
    }

    public function verify(array &$payload, string $signature): PresignedResult
    {
        $now = time();
        if (!isset($payload['expires'])) {
            return new PresignedResult(false, PresignedResult::ERROR_EXPIRED);
        }
        $expires = (int) $payload['expires'];
        if ($now > $expires) {
            return new PresignedResult(false, PresignedResult::ERROR_EXPIRED);
        }
        $expectedSignature = $this->hash($payload);
        if (!hash_equals($expectedSignature, $signature)) {
            return new PresignedResult(false, PresignedResult::ERROR_INVALID_SIGNATURE);
        }
        return new PresignedResult(true);
    }

    private function sign(array $payload, int $expiresIn = 900): string
    {
        $expires = time() + $expiresIn;

        $payload['expires'] = $expires;


        $signature = $this->hash($payload);

        $payload['signature'] = $signature;

        return http_build_query($payload);
    }

    private function hash(array $payload)
    {
        $keys = array_keys($payload);
        sort($keys);

        $sortedPayload = [];
        foreach ($keys as $key) {
            $sortedPayload[$key] = $payload[$key];
        }
        return hash_hmac('sha256', implode(':', $sortedPayload), $this->secret);
    }


}