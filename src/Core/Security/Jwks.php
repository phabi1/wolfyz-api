<?php

namespace App\Core\Security;

use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use GuzzleHttp\Client;

/**
 * Public keys of the auth server (JSON Web Key Set), used to check the
 * signature of the access tokens. Cached in a file; reloaded when the cache
 * expires or when a token is signed with an unknown key (key rotation).
 */
class Jwks
{
    private const TTL = 3600;
    /** Minimum delay between two forced reloads (unknown key id). */
    private const RELOAD_DELAY = 60;

    /** @var callable(string): string */
    private $fetcher;

    /**
     * @param callable(string): string|null $fetcher returns the JWKS document of the URI (tests)
     */
    public function __construct(
        private readonly ?string $uri,
        private readonly string $cacheFile,
        ?callable $fetcher = null,
    ) {
        $this->fetcher = $fetcher ?? static fn (string $uri): string => (string) (new Client(['timeout' => 5]))
            ->get($uri)
            ->getBody();
    }

    /**
     * @return array<string, Key> keys by key id
     */
    public function keys(bool $reload = false): array
    {
        $age = is_file($this->cacheFile) ? time() - filemtime($this->cacheFile) : null;
        $expired = $age === null || $age > self::TTL;
        $mayReload = $reload && ($age === null || $age > self::RELOAD_DELAY);

        if ($expired || $mayReload) {
            $this->refresh();
        }

        $document = is_file($this->cacheFile) ? json_decode((string) file_get_contents($this->cacheFile), true) : null;

        return is_array($document) ? JWK::parseKeySet($document, 'RS256') : [];
    }

    private function refresh(): void
    {
        if ($this->uri === null || $this->uri === '') {
            return;
        }
        try {
            $body = ($this->fetcher)($this->uri);
        } catch (\Throwable) {
            return; // keep the cached keys, if any
        }
        $document = json_decode($body, true);
        if (!is_array($document) || !isset($document['keys']) || !is_array($document['keys'])) {
            return;
        }
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($this->cacheFile, json_encode($document), LOCK_EX);
    }
}
