<?php

namespace Penguin\Component\Database;

use PDOStatement;

interface ConnectionInterface
{
    public function select(string $query, array $bindings = []): array;

    public function selectOne(string $query, array $bindings = []): mixed;

    public function insert(string $query, array $bindings = []): int;

    public function insertGetId(string $query, string $name, array $bindings = []): string|false;

    public function delete(string $query, array $bindings = []): int;

    public function update(string $query, array $bindings = []): int;

    public function statement(string $query, array $bindings = []): PDOStatement|false;

    public function bindValues(PDOStatement $statement, array $bindings): static;

    public function setReconnect(callable $reconnector): static;

    public function reconnect(): mixed;

    public function disconnect(): static;
}