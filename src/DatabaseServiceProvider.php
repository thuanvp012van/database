<?php

namespace Penguin\Component\Database;

use Penguin\Component\Container\ContainerInterface;
use Penguin\Component\Database\Model\Model;
use Penguin\Component\Database\Query\BuilderFactory;
use Penguin\Component\Database\ConnectionManager;
use Penguin\Component\Event\ListenerProviderInterface;
use Penguin\Component\Event\ListenerProvider;
use Penguin\Component\Event\EventDispatcher;
use Penguin\Component\Event\EventDispatcherInterface;

class DatabaseServiceProvider
{
    public function register(ContainerInterface $container)
    {
        $container->singleton(ConnectionManager::class, function () {
            return new ConnectionManager();
        });

        $container->singleton(BuilderFactory::class, function () {
            return new BuilderFactory(service(ConnectionManager::class));
        });

        if (!$container->has(ListenerProviderInterface::class)) {
            $container->singleton(ListenerProviderInterface::class, function () {
                return new ListenerProvider();
            });
        }

        if (!$container->has(EventDispatcherInterface::class)) {
            $container->singleton(EventDispatcherInterface::class, function ($container) {
                return new EventDispatcher($container->get(ListenerProviderInterface::class));
            });
        }
    }

    public function boot(ContainerInterface $container)
    {
        Model::setListenerProvider($container->get(ListenerProviderInterface::class));
        Model::setEventDispatcher($container->get(EventDispatcherInterface::class));
    }
}