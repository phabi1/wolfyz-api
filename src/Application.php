<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Route;
use App\Core\Di;

class Application
{
    private static $_instance;

    private $container;

    public function bootstrap()
    {
        $this->setupDependencyInjection();

        $this->setupConfig();

        $request = Request::createFromGlobals();

        $routes = $this->loadRoutes();

        $context = new RequestContext();
        $routeMatcher = new UrlMatcher($routes, $context);

        $context->fromRequest($request);

        try {
            $routeParameters = $routeMatcher->match($request->getPathInfo());
            $request->attributes->add($routeParameters);
        } catch (ResourceNotFoundException $e) {
            $response = new JsonResponse('Not Found', 404);
            $response->send();
            return;
        } catch (\Exception $e) {
            $response = new JsonResponse('An error occurred', 500);
            $response->send();
            return;
        }

        $controller = $routeParameters[0];
        $action = $routeParameters[1];

        $controllerInstance = new $controller();

        $controllerInstance->setContainer($this->container);
        $response = $controllerInstance->dispatch($action, $request);

        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:4200');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        $response->send();
    }

    private function loadRoutes()
    {
        $routes = require APP_DIR . '/config/routes.php';
        $collection = new RouteCollection();
        foreach ($routes as $name => $info) {
            $collection->add($name, new Route(
                $info['path'],
                $info['controller'],
                $info['requirements'] ?? [],
                $info['options'] ?? [],
                $info['host'] ?? '',
                $info['schemes'] ?? [],
                $info['methods'] ?? [],
                $info['condition'] ?? ''
            ));
        }
        return $collection;
    }

    private function setupDependencyInjection()
    {
        $definitions = require APP_DIR . '/config/services.php';

        $container = new Di\Container($definitions);
        $this->container = $container;

    }

    private function setupConfig()
    {
        $this->container->get('parameters')->load(CONFIG_DIR . '/parameters.php');
    }

    public static function run()
    {
        self::$_instance = new self();
        self::$_instance->bootstrap();
    }
}
