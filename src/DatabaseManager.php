<?php

namespace Penguin\Component\Database;

use Penguin\Component\Database\ConnectionManager;
use Penguin\Component\Container\ContainerInterface;
use Penguin\Component\Container\ServiceProviderManager;
use Penguin\Component\Database\DatabaseServiceProvider;

class DatabaseManager
{
    public function __construct(protected ContainerInterface $container)
    {
        $serviceProviderManager = new ServiceProviderManager($container);
        $serviceProviderManager->register(DatabaseServiceProvider::class);
        $serviceProviderManager->boot();
    }

    public function __call(string $name , array $arguments): mixed
    {
        return $this->container->get(ConnectionManager::class)->{$name}(...$arguments);
    }
}