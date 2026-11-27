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

    /** Why the last reload failed, if it did. */
    private ?string $error = null;

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
     * JWKS of the issuer: the configured URI, or by default
     * <issuer>/.well-known/jwks.json (OIDC discovery convention).
     */
    public static function forIssuer(?string $issuer, ?string $uri, string $cacheFile): self
    {
        if (($uri === null || $uri === '') && $issuer !== null && $issuer !== '') {
            $uri = rtrim($issuer, '/') . '/.well-known/jwks.json';
        }

        return new self($uri, $cacheFile);
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    /** Why the last reload failed (null when it succeeded or did not run). */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @return array<string, Key> keys by key id
     */
    public function keys(bool $reload = false): array
    {
        $age = is_file($this->cacheFile) ? time() - filemtime($this->cacheFile) : null;
        $expired = $age === null || $age > self::TTL;
        $mayReload = $reload && ($age === null || $age > self::RELOAD_DELAY);

        $document = ($expired || $mayReload) ? $this->refresh() : null;
        // Cached keys when not reloaded, or when the reload failed.
        $document ??= is_file($this->cacheFile) ? json_decode((string) file_get_contents($this->cacheFile), true) : null;

        return is_array($document) ? JWK::parseKeySet($document, 'RS256') : [];
    }

    /** @return array<string, mixed>|null the fetched document */
    private function refresh(): ?array
    {
        $this->error = null;
        if ($this->uri === null || $this->uri === '') {
            $this->error = 'no JWKS URI configured';
            return null;
        }
        try {
            $body = ($this->fetcher)($this->uri);
        } catch (\Throwable $e) {
            $this->error = sprintf('cannot fetch %s: %s', $this->uri, $e->getMessage());
            return null; // keep the cached keys, if any
        }
        $document = json_decode($body, true);
        if (!is_array($document) || !isset($document['keys']) || !is_array($document['keys'])) {
            $this->error = sprintf('%s is not a JWKS document', $this->uri);
            return null;
        }
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (@file_put_contents($this->cacheFile, json_encode($document), LOCK_EX) === false) {
            // Still usable for this request, fetched again on the next one.
            $this->error = sprintf('cannot write %s', $this->cacheFile);
        }

        return $document;
    }
}
