<?php

namespace App\Core\Mvc\Router;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutesFactory
{
    public static function create()
    {
        if (file_exists(CACHE_DIR . '/routes.php')) {
            $routes = require CACHE_DIR . '/routes.php';
        } else {
            $routes = require APP_DIR . '/config/routes.php';
            file_put_contents(CACHE_DIR . '/routes.php', '<?php return ' . var_export($routes, true) . ';');
        }

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
}