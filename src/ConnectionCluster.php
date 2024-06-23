<?php

namespace Penguin\Component\Database;

class ConnectionCluster implements ConnectionClusterInterface
{
    protected array $connections = [];

    protected array $configs = [];

    protected ?string $defaultConnection = null;

    public function addConnection(string $name, array $config): void
    {
        $this->configs[$name] = $config;
    }

    public function getConnection(string $name = null): ConnectionInterface
    {
        $name = is_null($name) ? $this->defaultConnection : $name;

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }
        if (isset($this->configs[$name])) {
            $connection = new Connection($this->configs[$name]);
            $this->connections[$name] = $connection;
            return $connection;
        }
        throw new \RuntimeException("");
    }

    public function getConfig(string $name = null): ?array
    {
        $name = is_null($name) ? $this->defaultConnection : $name;
        return !empty($this->configs[$name]) ? $this->configs[$name] : null;
    }

    public function setDefaultConnection(string $defaultConnection): static
    {
        $this->defaultConnection = $defaultConnection;
        return $this;
    }

    public function getDefaultConnection(): ?string
    {
        return $this->defaultConnection;
    }
}