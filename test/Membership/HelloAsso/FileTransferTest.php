<?php

namespace App\Test\Membership\HelloAsso;

use App\File\Presign\PresignedUrlService;
use App\Membership\HelloAsso\FileTransfer;
use App\Membership\HelloAsso\TransferAbortedException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class FileTransferTest extends TestCase
{
    private const URL = 'https://docs.helloasso.com/customFieldsAnswer/123456';

    private array $history = [];
    private int $tokenCalls = 0;

    public function testTransfersAPublicFile(): void
    {
        $transfer = $this->transfer([
            new Response(200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="certificat.pdf"'], '%PDF-1.7'),
            new Response(200, ['Content-Type' => 'application/json'], '{"success":true}'),
        ]);

        $key = $transfer->transfer(self::URL);

        $this->assertMatchesRegularExpression('#^membership/[0-9a-f]{12}/certificat\.pdf$#', $key);
        $this->assertCount(2, $this->history);
        $this->assertSame('GET', $this->history[0]['request']->getMethod());
        $this->assertFalse($this->history[0]['request']->hasHeader('Authorization'));

        $upload = $this->history[1]['request'];
        $this->assertSame('PUT', $upload->getMethod());
        $this->assertSame('files.test', $upload->getUri()->getHost());
        $this->assertSame('/api/' . $key, rawurldecode($upload->getUri()->getPath()));
        $this->assertStringContainsString('content-type=application%2Fpdf', $upload->getUri()->getQuery());
        $this->assertSame('application/pdf', $upload->getHeaderLine('Content-Type'));
        $this->assertSame('%PDF-1.7', (string) $upload->getBody());
        $this->assertSame(0, $this->tokenCalls);
    }

    public function testRetriesWithTheApiToken(): void
    {
        $transfer = $this->transfer([
            new Response(403),
            new Response(200, ['Content-Type' => 'image/jpeg'], 'JPEG'),
            new Response(200, [], '{"success":true}'),
        ]);

        $key = $transfer->transfer(self::URL);

        $this->assertSame(1, $this->tokenCalls);
        $this->assertSame('Bearer token', $this->history[1]['request']->getHeaderLine('Authorization'));
        // No file name given: named after the answer id, extension from the type
        $this->assertMatchesRegularExpression('#^membership/[0-9a-f]{12}/helloasso-123456\.jpg$#', $key);
    }

    public function testFileNameFromUtf8Disposition(): void
    {
        $transfer = $this->transfer([
            new Response(200, ['Content-Type' => 'image/png', 'Content-Disposition' => "attachment; filename*=UTF-8''photo%20d%27identit%C3%A9.png"], 'PNG'),
            new Response(200, [], '{"success":true}'),
        ]);

        $this->assertStringEndsWith("/photo d'identité.png", $transfer->transfer(self::URL));
    }

    public function testRefusesAWebPage(): void
    {
        $transfer = $this->transfer([
            new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], '<html>Connexion</html>'),
        ]);

        try {
            $transfer->transfer(self::URL);
            $this->fail('A web page was transferred');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('web page', $e->getMessage());
        }
        $this->assertCount(1, $this->history, 'Nothing uploaded');
    }

    public function testFailsWhenHelloAssoRefuses(): void
    {
        $transfer = $this->transfer([new Response(404), new Response(404)]);

        $this->expectExceptionMessage('HTTP 404');
        $transfer->transfer(self::URL);
    }

    public function testStopsWhenTheFilesServiceRefuses(): void
    {
        $transfer = $this->transfer([
            new Response(200, ['Content-Type' => 'application/pdf'], '%PDF'),
            new Response(405, ['Content-Type' => 'text/html'], '<html><h1>Method Not Allowed</h1></html>'),
        ]);

        $this->expectException(TransferAbortedException::class);
        $this->expectExceptionMessage('HTTP 405): Method Not Allowed');
        $transfer->transfer(self::URL);
    }

    public function testATooLargeFileOnlyFailsThisFile(): void
    {
        $transfer = $this->transfer([
            new Response(200, ['Content-Type' => 'application/pdf'], '%PDF'),
            new Response(413, [], '{"success":false,"error":{"code":"EntityTooLarge"}}'),
        ]);

        try {
            $transfer->transfer(self::URL);
            $this->fail('No error');
        } catch (\RuntimeException $e) {
            $this->assertNotInstanceOf(TransferAbortedException::class, $e);
            $this->assertStringContainsString('EntityTooLarge', $e->getMessage());
        }
    }

    public function testTheTokenIsRequestedOnce(): void
    {
        $transfer = $this->transfer([
            new Response(403),
            new Response(200, ['Content-Type' => 'application/pdf'], 'one'),
            new Response(200, [], '{"success":true}'),
            new Response(403),
            new Response(200, ['Content-Type' => 'application/pdf'], 'two'),
            new Response(200, [], '{"success":true}'),
        ]);

        $transfer->transfer(self::URL);
        $transfer->transfer(self::URL);

        $this->assertSame(1, $this->tokenCalls);
    }

    public function testStopsWhenRefusedEvenWithTheToken(): void
    {
        $transfer = $this->transfer([new Response(403), new Response(403)]);

        $this->expectException(TransferAbortedException::class);
        $this->expectExceptionMessage('even with the API token');
        $transfer->transfer(self::URL);
    }

    public function testStopsWhenRateLimited(): void
    {
        $transfer = $this->transfer([new Response(429)]);

        $this->expectException(TransferAbortedException::class);
        $this->expectExceptionMessage('HTTP 429');
        $transfer->transfer(self::URL);
    }

    public function testStopsWhenTheTokenIsRateLimited(): void
    {
        $stack = HandlerStack::create(new MockHandler([new Response(403)]));
        $transfer = new FileTransfer(
            new Client(['handler' => $stack]),
            new PresignedUrlService('https://files.test', 'api', 'FKTEST', 'secret'),
            static function (): string {
                throw new ClientException(
                    'Too Many Requests',
                    new Request('POST', 'https://api.helloasso.com/oauth2/token'),
                    new Response(429, [], 'error code: 1015'),
                );
            },
        );

        $this->expectException(TransferAbortedException::class);
        $this->expectExceptionMessage('HTTP 429');
        $transfer->transfer(self::URL);
    }

    public function testIsHelloAssoUrl(): void
    {
        $this->assertTrue(FileTransfer::isHelloAssoUrl(self::URL));
        $this->assertTrue(FileTransfer::isHelloAssoUrl(' https://docs.helloasso-sandbox.com/customFieldsAnswer/1 '));
        $this->assertFalse(FileTransfer::isHelloAssoUrl('membership/0123456789ab/certificat.pdf'));
        $this->assertFalse(FileTransfer::isHelloAssoUrl('Voir le document'));
        $this->assertFalse(FileTransfer::isHelloAssoUrl('https://helloasso.com.evil.example/x'));
        $this->assertFalse(FileTransfer::isHelloAssoUrl(null));
    }

    private function transfer(array $responses): FileTransfer
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new FileTransfer(
            new Client(['handler' => $stack]),
            new PresignedUrlService('https://files.test', 'api', 'FKTEST', 'secret'),
            function (): string {
                $this->tokenCalls++;

                return 'token';
            },
        );
    }
}
