<?php

return
    [
        'parameters' => [
            'class' => \App\Core\Config\Parameters::class
        ],
        'db' => [
            'factory' => [\App\Core\Db\DbFactory::class, 'create'],
            'arguments' => ['@parameters']
        ],
        'session' => [
            'factory' => [\App\Core\Session\SessionFactory::class, 'create'],
        ],
        'routes' => [
            'factory' => [\App\Core\Mvc\Router\RoutesFactory::class, 'create'],
        ],
        'router-matcher' => [
            'factory' => [\App\Core\Mvc\Router\RouterMatcherFactory::class, 'create'],
            'arguments' => ['@routes', '@router-context']
        ],
        'router-generator' => [
            'factory' => [\App\Core\Mvc\Router\RouterGeneratorFactory::class, 'create'],
            'arguments' => ['@routes', '@router-context']
        ],
        'router-context' => [
            'factory' => [\App\Core\Mvc\Router\RouterContextFactory::class, 'create'],
        ],
        'view' => [
            'factory' => [\App\Core\Mvc\View\ViewFactory::class, 'create'],
            'arguments' => ['@view.helpers']
        ],
        'view.helpers' => [
            'class' => \App\Core\Mvc\View\Helpers::class
        ],
        'use-case-bus' => [
            'class' => \App\Core\UseCase\UseCaseBus::class
        ],
        'entity.definition' => [
            'factory' => [\App\Core\Entity\EntityDefinitionFactory::class, 'create'],
        ],
        'entity.manager' => [
            'class' => \App\Core\Entity\EntityManager::class,
            'arguments' => ['@entity.definition', '@db']
        ],
        'translator' => [
            'class' => \App\Core\Translation\Translator::class
        ],
        'mailer' => [
            'class' => \App\Core\Mail\Mailer::class,
            'arguments' => ['@view', '@parameters']
        ],
        'event.dispatcher' => [
            'class' => \App\Core\Events\EventDispatcher::class
        ],
        'controller.helpers' => [
            'class' => \App\Core\Mvc\Controller\Helpers::class
        ],
        'controller.helper.identity' => [
            'class' => \App\Core\Mvc\Controller\Helper\Identity::class,
            'tags' => [['name' => 'controller.helper', 'value' => 'identity']]
        ],
        'controller.helper.use-case-bus' => [
            'class' => \App\Core\Mvc\Controller\Helper\UseCaseBus::class,
            'arguments' => ['@use-case-bus'],
            'tags' => [['name' => 'controller.helper', 'value' => 'use-case-bus']]
        ],
        'view.helper.asset' => [
            'class' => \App\Core\Mvc\View\Helper\Asset::class,
            'arguments' => ['!media.server'],
            'tags' => [['name' => 'view.helper', 'value' => 'asset']]
        ],
        'view.helper.translator' => [
            'class' => \App\Core\Mvc\View\Helper\Translator::class,
            'arguments' => ['@translator'],
            'tags' => [['name' => 'view.helper', 'value' => 'translator']]
        ],
        'view.helper.route' => [
            'class' => \App\Core\Mvc\View\Helper\Route::class,
            'arguments' => ['@router-generator'],
            'tags' => [['name' => 'view.helper', 'value' => 'route']]
        ],
        'helper.string' => [
            'class' => \App\Core\Helper\StringHelper::class
        ],
        'helper.date' => [
            'class' => \App\Core\Helper\DateHelper::class
        ],
        'security.firewall' => [
            'class' => \App\Core\Security\Firewall::class
        ],
        'security.strategy.api-key' => [
            'class' => \App\Core\Security\Strategy\ApiKeyStrategy::class,
            'arguments' => ['!security.strategy.api-key.secret'],
            'tags' => [['name' => 'security.strategy', 'value' => 'api-key']]
        ],
        'security.strategy.public' => [
            'class' => \App\Core\Security\Strategy\PublicStrategy::class,
            'tags' => [['name' => 'security.strategy', 'value' => 'public']]
        ]
    ];