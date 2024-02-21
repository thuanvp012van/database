<?php

namespace Penguin\Component\Database;

class ConnectionManager
{
    protected PoolClusterInterface|ConnectionClusterInterface $db;

    public function createPoolCluster(): PoolClusterInterface
    {
        $this->db = new PoolCluster();
        return $this->db;
    }

    public function createConnectionCluster(): ConnectionClusterInterface
    {
        $this->db = new ConnectionCluster();
        return $this->db;
    }

    public function getConnection(string $groupName = null): ConnectionInterface
    {
        if ($this->db instanceof ConnectionClusterInterface) {
            return $this->db->getConnection($groupName);
        }

        $pool = $this->db->getPool($groupName);
        return $pool->get();
    }
}