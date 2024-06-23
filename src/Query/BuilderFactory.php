<?php

namespace Penguin\Component\Database\Query;

use Penguin\Component\Database\ConnectionManager;

class BuilderFactory
{
    public function __construct(protected ConnectionManager $connectionManager) {}

    public function make(string $connectionName = null): Builder
    {
        $cluster = $this->connectionManager->getCluster();
        $config = $cluster->getConfig($connectionName);
        $driver = $config['driver'];
        return match ($driver) {
            'mysql' => new MysqlBuilder($this->connectionManager),
            'postgres' => new PostgreBuilder($this->connectionManager),
            'sqlite' => new MysqlBuilder($this->connectionManager)
        };
    }
}