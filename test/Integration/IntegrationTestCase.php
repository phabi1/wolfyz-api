<?php

declare(strict_types=1);

namespace App\Test\Integration;

use App\Core\Di\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class IntegrationTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootstrapApplicationConstants();
        self::ensureCacheDirectory();
        self::clearRoutesCache();
    }

    protected function createContainer(): Container
    {
        $container = new Container(require APP_DIR . '/config/services.php');
        $container->get('parameters')->load(CONFIG_DIR . '/parameters.test.php');

        return $container;
    }

    protected function dispatchRequest(string $path, string $method = 'GET', array $headers = []): Response
    {
        $container = $this->createContainer();
        $request = Request::create($path, $method);

        foreach ($headers as $name => $value) {
            $request->headers->set($name, (string) $value);
        }

        $context = $container->get('router-context');
        $context->fromRequest($request);

        $routeParameters = $container->get('router-matcher')->match($request->getPathInfo());
        $request->attributes->add($routeParameters);

        $firewall = $container->get('security.firewall');
        self::assertTrue($firewall->authenticate($request), 'Request was rejected by firewall.');

        $controller = $routeParameters[0];
        $action = $routeParameters[1];

        $controllerInstance = new $controller();
        $controllerInstance->setContainer($container);

        return $controllerInstance->dispatch($action, $request);
    }

    private static function bootstrapApplicationConstants(): void
    {
        if (!defined('APP_ENV')) {
            define('APP_ENV', 'test');
        }
        if (!defined('APP_DIR')) {
            define('APP_DIR', dirname(__DIR__, 2));
        }
        if (!defined('CONFIG_DIR')) {
            define('CONFIG_DIR', APP_DIR . '/config');
        }
        if (!defined('CACHE_DIR')) {
            define('CACHE_DIR', APP_DIR . '/cache');
        }
    }

    private static function ensureCacheDirectory(): void
    {
        if (!is_dir(CACHE_DIR)) {
            mkdir(CACHE_DIR, 0775, true);
        }
    }

    private static function clearRoutesCache(): void
    {
        $routesCache = CACHE_DIR . '/routes.php';
        if (file_exists($routesCache)) {
            unlink($routesCache);
        }
    }
}
