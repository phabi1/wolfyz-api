<?php

namespace App\File\Presign;

/**
 * Request signature (HMAC-SHA256 with the secret of an access key).
 *
 * The same signature is sent either in headers (requests of a server) or in
 * the query string (presigned URL, handed to a browser):
 *
 *   X-Files-Key        access key id
 *   X-Files-Expires    Unix time after which the request is refused
 *   X-Files-Signature  hex HMAC-SHA256 of the string to sign
 *
 * String to sign (lines joined by "\n"):
 *
 *   FILES-HMAC-SHA256
 *   <METHOD>            uppercase, HEAD signed as GET
 *   <path>              decoded path relative to the service URL, each
 *                       segment encoded with rawurlencode: /bucket/a%20b.pdf
 *   <query>             the other query parameters, sorted by name,
 *                       name=value encoded with rawurlencode, joined by "&"
 *   <key id>
 *   <expires>
 *
 * The body is not signed: send requests over HTTPS. Every other query
 * parameter is signed, e.g. "content-type" of a presigned upload.
 *
 * Copy of App\Security\Signer of the files application: keep them identical.
 */
final class Signer
{
    public const ALGORITHM = 'FILES-HMAC-SHA256';
    public const KEY = 'X-Files-Key';
    public const EXPIRES = 'X-Files-Expires';
    public const SIGNATURE = 'X-Files-Signature';

    public static function stringToSign(string $method, string $path, array $query, string $keyId, int $expires): string
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        return implode("\n", [
            self::ALGORITHM,
            $method,
            self::canonicalPath($path),
            self::canonicalQuery($query),
            $keyId,
            (string) $expires,
        ]);
    }

    public static function sign(string $secret, string $method, string $path, array $query, string $keyId, int $expires): string
    {
        return hash_hmac('sha256', self::stringToSign($method, $path, $query, $keyId, $expires), $secret);
    }

    /**
     * @param string $path decoded path, e.g. "/bucket/my file.pdf"
     */
    public static function canonicalPath(string $path): string
    {
        $segments = explode('/', '/' . ltrim($path, '/'));

        return implode('/', array_map('rawurlencode', $segments));
    }

    public static function canonicalQuery(array $query): string
    {
        unset($query[self::KEY], $query[self::EXPIRES], $query[self::SIGNATURE]);
        ksort($query, SORT_STRING);

        $pairs = [];
        foreach ($query as $name => $value) {
            if (!is_scalar($value) && $value !== null) {
                throw new \InvalidArgumentException(sprintf('The query parameter "%s" must be a string', $name));
            }
            $pairs[] = rawurlencode((string) $name) . '=' . rawurlencode((string) $value);
        }

        return implode('&', $pairs);
    }
}
