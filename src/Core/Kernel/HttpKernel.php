<?php
namespace App\Core\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use App\Core\Security\Firewall;

class HttpKernel extends Base
{
    public function run()
    {
        $this->bootstrap();
        $request = Request::createFromGlobals();

        $referer = $request->headers->get('Origin');

        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse(null, 204);
            $this->withCors($response, $referer);
            $response->send();
            return;
        }

        $container = $this->getContainer();

        $urlMatcher = $container->get('router-matcher');
        $context = $container->get('router-context');

        $context->fromRequest($request);

        try {
            $routeParameters = $urlMatcher->match($request->getPathInfo());

            $request->attributes->add($routeParameters);

            /**
             * @var Firewall
             */
            $firewall = $container->get('security.firewall');
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

            $controllerInstance->setContainer($container);
            $response = $controllerInstance->dispatch($action, $request);

            $this->withCors($response, $referer);
            // You can add additional logic here, such as checking user permissions with the firewall
        } catch (ResourceNotFoundException $e) {
            $response = new JsonResponse(['message' => 'Not Found'], 404);
        } catch (MethodNotAllowedException $e) {
            $response = new JsonResponse(['message' => 'Method Not Allowed'], 405);
        } catch (\Exception $e) {
            $container->get('watchdog')->error($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            $response = new JsonResponse(['message' => 'An error occurred', 'exception' => $e->getMessage()], 500);

        }

        if (isset($response)) {
            $this->withCors($response, $referer);
        }
        $response?->send();

    }

    private function withCors($response, $referer)
    {
        $response->headers->set('Access-Control-Allow-Origin', $referer);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Api-Key');
    }
}