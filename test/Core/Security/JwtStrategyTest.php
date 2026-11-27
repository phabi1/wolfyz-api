<?php

declare(strict_types=1);

namespace App\Test\Core\Security;

use App\Core\Di\Locator;
use App\Core\Security\Firewall;
use App\Core\Security\Identity;
use App\Core\Security\Jwks;
use App\Core\Security\Strategy\JwtStrategy;
use App\Core\Security\Strategy\PublicStrategy;
use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

final class JwtStrategyTest extends TestCase
{
    private const ISSUER = 'https://auth.example.com';

    private static string $privateKey;
    private static string $otherPrivateKey;
    private static array $jwk;
    private string $cacheFile;
    private int $fetches = 0;

    public static function setUpBeforeClass(): void
    {
        [self::$privateKey, self::$jwk] = self::keyPair('key-1');
        [self::$otherPrivateKey] = self::keyPair('key-1');
    }

    /** @return array{0: string, 1: array} */
    private static function keyPair(string $kid): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $private);
        $rsa = openssl_pkey_get_details($key)['rsa'];
        $b64 = static fn (string $data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        return [$private, ['kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => $kid, 'n' => $b64($rsa['n']), 'e' => $b64($rsa['e'])]];
    }

    protected function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir() . '/jwks-test-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->cacheFile);
    }

    private function strategy(?array $jwks = null): JwtStrategy
    {
        $document = json_encode(['keys' => $jwks ?? [self::$jwk]]);
        $fetcher = function () use ($document): string {
            $this->fetches++;
            return $document;
        };

        return new JwtStrategy(new Jwks('https://auth.example.com/jwks', $this->cacheFile, $fetcher), self::ISSUER);
    }

    private function token(array $claims = [], ?string $key = null, string $kid = 'key-1'): string
    {
        return JWT::encode($claims + [
            'iss' => self::ISSUER,
            'aud' => 'club',
            'jti' => 'token-1',
            'sub' => 'user-1',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'scopes' => ['openid', 'api'],
            'permissions' => ['membership.request.view'],
        ], $key ?? self::$privateKey, 'RS256', $kid);
    }

    private function request(?string $token): Request
    {
        $request = Request::create('/');
        if ($token !== null) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $request;
    }

    public function testAcceptsAValidAccessToken(): void
    {
        $request = $this->request($this->token());

        self::assertTrue($this->strategy()->authenticate($request));
        $identity = $request->attributes->get('_identity');
        self::assertInstanceOf(Identity::class, $identity);
        self::assertSame('user-1', $identity->getId());
        self::assertSame(['membership.request.view'], $identity->getPermissions());
    }

    public static function invalidTokens(): array
    {
        return [
            'other issuer' => [['iss' => 'https://evil.example.com']],
            'no api scope' => [['scopes' => ['openid']]],
            'ID token (no scopes)' => [['scopes' => null]],
            'expired' => [['exp' => time() - 3600]],
            'no subject' => [['sub' => '']],
        ];
    }

    #[DataProvider('invalidTokens')]
    public function testRejectsInvalidClaims(array $claims): void
    {
        self::assertFalse($this->strategy()->authenticate($this->request($this->token($claims))));
    }

    public function testRejectsAForgedSignature(): void
    {
        self::assertFalse($this->strategy()->authenticate($this->request($this->token([], self::$otherPrivateKey))));
    }

    public function testRejectsAMissingOrMalformedHeader(): void
    {
        self::assertFalse($this->strategy()->authenticate($this->request(null)));
        self::assertFalse($this->strategy()->authenticate($this->request('not-a-jwt')));
    }

    public function testRejectsEverythingWithoutIssuer(): void
    {
        $strategy = new JwtStrategy(new Jwks(null, $this->cacheFile), null);

        self::assertFalse($strategy->authenticate($this->request($this->token())));
    }

    public function testCachesTheKeys(): void
    {
        $strategy = $this->strategy();
        $strategy->authenticate($this->request($this->token()));
        $strategy->authenticate($this->request($this->token()));

        self::assertSame(1, $this->fetches);
    }

    public function testReloadsTheKeysForAnUnknownKeyId(): void
    {
        // Stale cache without the current key, older than the reload delay.
        file_put_contents($this->cacheFile, json_encode(['keys' => [array_merge(self::$jwk, ['kid' => 'old'])]]));
        touch($this->cacheFile, time() - 120);

        self::assertTrue($this->strategy()->authenticate($this->request($this->token())));
        self::assertSame(1, $this->fetches);
    }

    private function firewall(): Firewall
    {
        $strategies = [
            'jwt' => $this->strategy(),
            'public' => new PublicStrategy(),
        ];
        $locator = new class ($strategies) extends Locator {
            public function __construct(private array $strategies)
            {
            }

            public function get(string $key): mixed
            {
                return $this->strategies[$key];
            }
        };

        return new Firewall($locator);
    }

    private static function route(array $auth, array $permission): Route
    {
        return new Route('/', options: ['_auth' => $auth, '_permission' => $permission]);
    }

    public function testFirewall(): void
    {
        $firewall = $this->firewall();
        $user = fn () => $this->request($this->token());
        $site = fn () => $this->request($this->token([
            'aud' => 'wordpress',
            'sub' => 'wordpress',
            'scopes' => ['api'],
            'permissions' => ['membership.registration'],
        ]));
        $read = self::route(['jwt'], ['membership.request.view']);
        $write = self::route(['jwt'], ['membership.request.edit']);
        $form = self::route(['jwt'], ['membership.subscription.edit', 'membership.registration']);
        $public = self::route(['public'], []);

        self::assertSame(Firewall::GRANTED, $firewall->check($user(), $read));
        self::assertSame(Firewall::FORBIDDEN, $firewall->check($user(), $write));
        self::assertSame(Firewall::UNAUTHENTICATED, $firewall->check($this->request(null), $read));
        // Client credentials token of the website: only its own permissions.
        self::assertSame(Firewall::GRANTED, $firewall->check($site(), $form));
        self::assertSame(Firewall::FORBIDDEN, $firewall->check($site(), $read));
        self::assertSame(Firewall::FORBIDDEN, $firewall->check($user(), $form));
        self::assertSame(Firewall::GRANTED, $firewall->check($this->request(null), $public));
        // No permission declared: denied.
        self::assertSame(Firewall::FORBIDDEN, $firewall->check($user(), self::route(['jwt'], [])));
        self::assertSame(Firewall::FORBIDDEN, $firewall->check($user(), null));
    }
}
