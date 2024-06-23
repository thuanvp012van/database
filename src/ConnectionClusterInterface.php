<?php

namespace Penguin\Component\Database;

use Penguin\Component\Database\ConnectionInterface;

interface ConnectionClusterInterface
{
    public function addConnection(string $name, array $config): void;

    public function getConnection(string $name = null): ConnectionInterface;

    public function getConfig(string $name = null): ?array;

    public function setDefaultConnection(string $defaultConnection): static;

    public function getDefaultConnection(): ?string;
}