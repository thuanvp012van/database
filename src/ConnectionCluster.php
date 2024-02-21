<?php

namespace Penguin\Component\Database;

class ConnectionCluster implements ConnectionClusterInterface
{
    protected array $cluster = [];

    public function addConnector(string $name, callable $connector): void
    {
        $this->cluster[$name] = $connector;
    }

    public function getConnection(string $name): ConnectionInterface
    {
        if (is_callable($this->cluster[$name])) {
            $this->cluster[$name] = $this->cluster[$name]();
        }
        return $this->cluster[$name];
    }
}