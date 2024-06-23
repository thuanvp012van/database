<?php

namespace Penguin\Component\Database;

class ConnectionManager
{
    protected ConnectionClusterInterface $db;

    public function createCluster(): ConnectionClusterInterface
    {
        $this->db = new ConnectionCluster();
        return $this->db;
    }

    public function getCluster(): ConnectionClusterInterface
    {
        return $this->db;
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->getCluster()->{$method}(...$arguments);
    }
}