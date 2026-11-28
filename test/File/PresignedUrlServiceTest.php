<?php

namespace App\Test\File;

use App\File\Presign\PresignedUrlService;
use App\File\Presign\Signer;
use PHPUnit\Framework\TestCase;

class PresignedUrlServiceTest extends TestCase
{
    /**
     * Same reference value as tests/SignerTest.php of the files service: the
     * two copies of the signer must agree.
     */
    public function testSignatureMatchesTheFilesService(): void
    {
        $this->assertSame(
            hash_hmac('sha256', "FILES-HMAC-SHA256\nPUT\n/api/a.txt\ncontent-type=text%2Fplain\nFKTEST\n1700000000", 'secret'),
            Signer::sign('secret', 'PUT', '/api/a.txt', ['content-type' => 'text/plain'], 'FKTEST', 1700000000),
        );
    }

    public function testUploadUrl(): void
    {
        $service = new PresignedUrlService('https://files.test/', 'api', 'FKTEST', 'secret');
        $url = $service->upload('membership/abc/certificat médical.pdf', 'application/pdf');

        $parts = parse_url($url);
        $this->assertSame('files.test', $parts['host']);
        $this->assertSame('/api/membership/abc/certificat%20m%C3%A9dical.pdf', $parts['path']);

        parse_str($parts['query'], $query);
        $this->assertSame('application/pdf', $query['content-type']);
        $this->assertSame('FKTEST', $query[Signer::KEY]);
        $this->assertSame(
            Signer::sign('secret', 'PUT', '/api/membership/abc/certificat médical.pdf', $query, 'FKTEST', (int) $query[Signer::EXPIRES]),
            $query[Signer::SIGNATURE],
        );
        $this->assertEqualsWithDelta(time() + 900, (int) $query[Signer::EXPIRES], 5);
    }

    public function testNewKeyKeepsTheFileName(): void
    {
        $service = new PresignedUrlService('https://files.test', 'api', 'FKTEST', 'secret');

        $this->assertMatchesRegularExpression('#^membership/[0-9a-f]{12}/photo d\'identité\.jpg$#', $service->newKey('membership', "photo d'identité.jpg"));
        $this->assertMatchesRegularExpression('#^event/4/[0-9a-f]{12}/b\.pdf$#', $service->newKey('event/4/', '../../a/b.pdf'));
        $this->assertMatchesRegularExpression('#^membership/[0-9a-f]{12}/file$#', $service->newKey('membership', '..'));
        $this->assertNotSame($service->newKey('membership', 'a.pdf'), $service->newKey('membership', 'a.pdf'));
    }
}
