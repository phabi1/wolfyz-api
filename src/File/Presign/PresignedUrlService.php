<?php

namespace App\File\Presign;

/**
 * Presigned URLs of the files service (bucket of the API, private). The
 * browser uploads, downloads and deletes the files there directly:
 *
 * - PUT <url>    body: the file, Content-Type: the given MIME type
 *                → {"success": true, "uri": "<file>"}
 * - GET <url>    the file
 * - DELETE <url> → {"success": true, "deleted": bool}
 *
 * <file> (the key of the object) is what the API stores, e.g.
 * "membership/3f9c0a6b1d2e/certificat.pdf".
 */
class PresignedUrlService
{
    /**
     * Parameters file.server, file.bucket, file.key_id, file.secret; checked
     * when a URL is made, so that a missing one is named in the error.
     */
    public function __construct(
        private ?string $server,
        private ?string $bucket,
        private ?string $keyId,
        private ?string $secret,
    ) {
    }

    public function upload(string $file, string $mimeType, int $expiresIn = 900): string
    {
        return $this->presign('PUT', $file, ['content-type' => $mimeType], $expiresIn);
    }

    public function remove(string $file, int $expiresIn = 900): string
    {
        return $this->presign('DELETE', $file, [], $expiresIn);
    }

    public function download(string $file, int $expiresIn = 900): string
    {
        return $this->presign('GET', $file, [], $expiresIn);
    }

    /**
     * New file key in the directory: "<directory>/<random>/<file name>". The
     * random part keeps uploads of the same name apart.
     */
    public function newKey(string $directory, string $originalFilename): string
    {
        $name = basename(str_replace('\\', '/', $originalFilename));
        $name = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '');
        if ($name === '' || $name === '.' || $name === '..') {
            $name = 'file';
        }
        if (strlen($name) > 200) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $name = mb_strcut($name, 0, 190, 'UTF-8') . ($extension !== '' ? '.' . mb_strcut($extension, 0, 9, 'UTF-8') : '');
        }

        return trim($directory, '/') . '/' . bin2hex(random_bytes(6)) . '/' . $name;
    }

    private function presign(string $method, string $file, array $query, int $expiresIn): string
    {
        $file = ltrim($file, '/');
        if ($file === '') {
            throw new \InvalidArgumentException('The file is empty');
        }

        foreach (['server' => $this->server, 'bucket' => $this->bucket, 'key_id' => $this->keyId, 'secret' => $this->secret] as $name => $value) {
            if ($value === null || trim($value) === '') {
                throw new \RuntimeException(sprintf('The files service is not configured: set file.%s in the parameters', $name));
            }
        }

        $path = '/' . $this->bucket . '/' . $file;
        $expires = time() + $expiresIn;

        $query[Signer::KEY] = $this->keyId;
        $query[Signer::EXPIRES] = $expires;
        $query[Signer::SIGNATURE] = Signer::sign($this->secret, $method, $path, $query, $this->keyId, $expires);

        return rtrim($this->server, '/') . Signer::canonicalPath($path) . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
