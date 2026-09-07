<?php

declare(strict_types=1);

namespace App\Test\Integration;

use Symfony\Component\HttpFoundation\JsonResponse;

final class WelcomeEndpointIntegrationTest extends IntegrationTestCase
{
    public function testWelcomeRouteReturnsExpectedPayload(): void
    {
        $response = $this->dispatchRequest('/');

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(['message' => 'Welcome to API!'], json_decode((string) $response->getContent(), true));
    }
}
