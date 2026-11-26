<?php

namespace App\Test\Membership\Controller;

use App\Membership\Controller\SubscriptionController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class SubscriptionControllerTest extends TestCase
{
    public static function exportFormats(): array
    {
        return [
            'csv' => ['csv', 'text/csv'],
            'xlsx' => ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];
    }

    #[DataProvider('exportFormats')]
    public function testExportReturnsFileAndDeletesItAfterSending(string $format, string $contentType): void
    {
        $file = tempnam(sys_get_temp_dir(), 'subscription_test_');
        self::assertNotFalse($file);
        file_put_contents($file, 'export content');

        try {
            $controller = new class(['file_url' => $file, 'format' => $format]) extends SubscriptionController {
                public function __construct(private array $result) {}

                public function __call($method, $args)
                {
                    if ($method === 'useCaseBus') {
                        return $this->result;
                    }

                    return parent::__call($method, $args);
                }
            };
            $request = Request::create('/export', 'GET');
            $request->attributes->set('campaign_id', 1);

            $response = $controller->dispatch('export', $request);

            self::assertInstanceOf(BinaryFileResponse::class, $response);
            self::assertSame($contentType, $response->headers->get('Content-Type'));
            self::assertSame('attachment; filename=subscriptions_export.' . $format, $response->headers->get('Content-Disposition'));
            self::assertFileExists($file);

            ob_start();
            try {
                $response->sendContent();
                $content = ob_get_contents();
            } finally {
                ob_end_clean();
            }

            self::assertSame('export content', $content);
            self::assertFileDoesNotExist($file);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testExportRequiresCampaignId(): void
    {
        $response = (new SubscriptionController())->dispatch('export', Request::create('/export'));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(400, $response->getStatusCode());
    }
}
