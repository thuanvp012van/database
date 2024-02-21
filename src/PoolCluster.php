<?php

namespace Penguin\Component\Database;

class PoolCluster implements PoolClusterInterface
{
    protected array $cluster = [];

    public function addPool(string $group, PoolInterface $pool): void
    {
        $this->cluster[$group] = $pool;
    }

    public function getPool(string $group): PoolInterface
    {
        return $this->cluster[$group];
    }
}