<?php

namespace App;

use App\Core\Security\Firewall;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
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

        $referer = $request->headers->get('Origin');

        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse(null, 204);
            $this->withCors($response, $referer);
            $response->send();
            return;
        }

        $urlMatcher = $this->container->get('router-matcher');
        $context = $this->container->get('router-context');

        $context->fromRequest($request);

        try {
            $routeParameters = $urlMatcher->match($request->getPathInfo());

            $request->attributes->add($routeParameters);
    
            /**
             * @var Firewall
             */
            $firewall = $this->container->get('security.firewall');
            $authenticated = $firewall->authenticate($request);
            if (!$authenticated) {
                $response = new JsonResponse(['message' => 'Unauthorized'], 401);
                $this->withCors($response, $referer);
                $response->send();
                return;
            }

            $controller = $routeParameters['_controller'][0];
            $action = $routeParameters['_controller'][1];

            $controllerInstance = new $controller();

            $controllerInstance->setContainer($this->container);
            $response = $controllerInstance->dispatch($action, $request);

            $this->withCors($response, $referer);
            // You can add additional logic here, such as checking user permissions with the firewall
        } catch (ResourceNotFoundException $e) {
            $response = new JsonResponse(['message' => 'Not Found'], 404);
        } catch (MethodNotAllowedException $e) {
            $response = new JsonResponse(['message' => 'Method Not Allowed'], 405);
        } catch (\Exception $e) {
            $this->container->get('watchdog')->error($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            $response = new JsonResponse(['message' => 'An error occurred', 'exception' => $e->getMessage()], 500);

        }

        if (isset($response)) {
            $this->withCors($response, $referer);
        }
        $response->send();
    }

    private function withCors($response, $referer)
    {
        $response->headers->set('Access-Control-Allow-Origin', $referer);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Api-Key');
    }

    private function setupDependencyInjection()
    {
        $definitions = require APP_DIR . '/config/services.php';

        $container = new Di\Container($definitions);
        $this->container = $container;

    }

    private function setupConfig()
    {
        $env = APP_ENV;
        $this->container->get('parameters')->load(CONFIG_DIR . '/parameters.' . $env . '.php');
    }

    public static function run()
    {
        self::$_instance = new self();
        self::$_instance->bootstrap();
    }
}
