<?php

namespace Penguin\Component\Database;

use Penguin\Component\Database\ConnectionInterface;

interface ConnectionClusterInterface
{
    public function addConnector(string $name, callable $connector): void;

    public function getConnection(string $name): ConnectionInterface;
}