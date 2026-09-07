<?php

namespace App\Core\Events;

use App\Core\Di\Container;
use App\Core\Di\LazyService;

class EventDispatcherFactory
{
    public static function create(Container $container): EventDispatcher
    {
        $dispatcher = new EventDispatcher();

        $serviceIds = $container->findByTag('event.listener');
        foreach ($serviceIds as $id) {
            $definition = $container->getDefinition($id);
            $listeners = array_filter($definition['tags'] ?? [], function ($tag) {
                return isset($tag['event']) && isset($tag['method']);
            });
            foreach ($listeners as $listener) {
                $service = new LazyService($id);
                $service->setContainer($container);
                $dispatcher->addListener($listener['event'], [$service, $listener['method']], $listener['priority'] ?? 0);
            }
        }

        return $dispatcher;
    }
}