<?php

namespace Penguin\Component\Database;

use Penguin\Component\Container\Container;
use Penguin\Component\Database\Query\Builder;
use Penguin\Component\Database\ConnectionManager;
use Penguin\Component\Database\ConnectionInterface;

class DB
{
    public static function query(): Builder
    {
        return new Builder(static::getConnectionManager());
    }

    public static function connect(string $connection = null): ConnectionInterface
    {
        $cluster = static::getConnectionManager()->getCluster();
        return $cluster->getConnection($connection);
    }

    protected static function getConnectionManager(): ConnectionManager
    {
        return Container::getInstance()->get(ConnectionManager::class);
    }
}