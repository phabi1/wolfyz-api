<?php

declare(strict_types=1);

namespace App\Test;

use App\Application;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApplicationTest extends TestCase
{
    public function testWithCorsAddsExpectedHeaders(): void
    {
        $application = new Application();
        $response = new JsonResponse(['ok' => true]);

        $method = new ReflectionMethod(Application::class, 'withCors');
        $method->setAccessible(true);
        $method->invoke($application, $response, 'https://club.wolfzy.local');

        self::assertSame('https://club.wolfzy.local', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertSame('GET, POST, PUT, DELETE, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
        self::assertSame('Content-Type, Authorization, X-Api-Key', $response->headers->get('Access-Control-Allow-Headers'));
    }
}
