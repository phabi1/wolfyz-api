<?php

namespace App\Membership\HelloAsso;

use App\File\Presign\PresignedUrlService;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Copies a file answered on a HelloAsso form (links of the exports, e.g.
 * https://docs.helloasso.com/customFieldsAnswer/123456) to the files
 * service, and returns its key there.
 */
class FileTransfer
{
    private const EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
    ];

    /**
     * @param \Closure(): string $accessToken HelloAsso API token, sent when
     *     the link refuses anonymous downloads
     */
    public function __construct(
        private ClientInterface $http,
        private PresignedUrlService $presignedUrls,
        private \Closure $accessToken,
    ) {
    }

    /**
     * Link to a file stored by HelloAsso (to transfer). Anything else (file
     * key, "Voir le document", empty) is not.
     */
    public static function isHelloAssoUrl(?string $value): bool
    {
        return $value !== null && (bool) preg_match('#^https?://([a-z0-9-]+\.)*helloasso(-sandbox)?\.com/#i', trim($value));
    }

    /**
     * @return string key of the file on the files service
     *
     * @throws \RuntimeException when HelloAsso or the files service refuses
     */
    public function transfer(string $url, string $directory = 'membership'): string
    {
        $response = $this->download(trim($url));

        $contentType = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'), 2)[0]));
        if ($contentType === 'text/html') {
            throw new \RuntimeException('HelloAsso answered with a web page instead of the file (login needed?)');
        }
        if ($contentType === '' || !preg_match('#^[a-z0-9][\w.+-]*/[\w.+-]+$#', $contentType)) {
            $contentType = 'application/octet-stream';
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $key = $this->presignedUrls->newKey($directory, $this->filename($response, $url, $contentType));
        $upload = $this->http->request('PUT', $this->presignedUrls->upload($key, $contentType), [
            'body' => $body,
            'headers' => ['Content-Type' => $contentType],
            'http_errors' => false,
        ]);
        if ($upload->getStatusCode() !== 200) {
            throw new \RuntimeException(sprintf(
                'The files service refused the upload (HTTP %d): %s',
                $upload->getStatusCode(),
                substr((string) $upload->getBody(), 0, 300),
            ));
        }

        return $key;
    }

    private function download(string $url): ResponseInterface
    {
        $options = [
            'http_errors' => false,
            // Guzzle drops the Authorization header on redirects to another host
            'allow_redirects' => ['max' => 5, 'track_redirects' => true],
            'timeout' => 120,
        ];

        $response = $this->http->request('GET', $url, $options);
        if (in_array($response->getStatusCode(), [401, 403], true)) {
            $options['headers'] = ['Authorization' => 'Bearer ' . ($this->accessToken)()];
            $response = $this->http->request('GET', $url, $options);
        }

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(sprintf('HelloAsso answered HTTP %d', $response->getStatusCode()));
        }

        return $response;
    }

    /**
     * File name from Content-Disposition, else the last URL of the redirects
     * when it has an extension, else "helloasso-<id>.<extension>".
     */
    private function filename(ResponseInterface $response, string $url, string $contentType): string
    {
        $disposition = $response->getHeaderLine('Content-Disposition');
        if (preg_match("/filename\\*\\s*=\\s*(?:UTF-8|utf-8)''([^;]+)/", $disposition, $match)) {
            $name = rawurldecode(trim($match[1]));
        } elseif (preg_match('/filename\s*=\s*"?([^";]+)"?/i', $disposition, $match)) {
            $name = trim($match[1]);
        } else {
            $redirects = $response->getHeader('X-Guzzle-Redirect-History');
            $last = basename((string) parse_url((string) end($redirects), PHP_URL_PATH));
            $name = pathinfo($last, PATHINFO_EXTENSION) !== '' ? rawurldecode($last) : '';
        }

        $name = basename(str_replace('\\', '/', $name));
        if ($name === '' || $name === '.' || $name === '..') {
            $id = basename((string) parse_url($url, PHP_URL_PATH)) ?: 'document';
            $name = 'helloasso-' . preg_replace('/[^A-Za-z0-9_-]/', '', $id);
        }
        if (pathinfo($name, PATHINFO_EXTENSION) === '' && isset(self::EXTENSIONS[$contentType])) {
            $name .= '.' . self::EXTENSIONS[$contentType];
        }

        return $name;
    }
}
