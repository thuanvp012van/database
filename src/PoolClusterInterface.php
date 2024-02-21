<?php

namespace Penguin\Component\Database;

interface PoolClusterInterface
{
    public function addPool(string $group, PoolInterface $pool);

    public function getPool(string $group): PoolInterface;
}