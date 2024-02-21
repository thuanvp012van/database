<?php

namespace Penguin\Component\Database;

use PDOStatement;
use PDOException;
use Closure;
use PDO;

class Connection implements ConnectionInterface
{
    protected string $query = '';

    protected array $params = [];

    protected ?PDO $pdo;

    protected ?Closure $reconnector = null;

    public function __construct(string $dns, protected string $prefix = '', array $options = []) {
        $this->pdo = new PDO($dns, options: $options);
    }

    public function select(string $query, array $bindings = []): array
    {
        return $this->statement($query, $bindings)->fetchAll();
    }

    public function selectOne(string $query, array $bindings = []): mixed
    {
        return array_shift($this->select($query, $bindings));
    }

    public function insert(string $query, array $bindings = []): int
    {
        return $this->statement($query, $bindings)->rowCount();
    }

    public function insertGetId(string $query, string $name, array $bindings = []): string|false
    {
        $this->statement($query, $bindings);
        return $this->lastInsertId($name);
    }

    public function delete(string $query, array $bindings = []): int
    {
        return $this->statement($query, $bindings)->rowCount();
    }

    public function update(string $query, array $bindings = []): int
    {
        return $this->statement($query, $bindings)->rowCount();
    }

    public function statement(string $query, array $bindings = []): PDOStatement|false
    {
        $statement = $this->pdo->prepare($query);
        $this->bindValues($statement, $bindings);
        $statement->execute();
        return $statement;
    }

    public function bindValues(PDOStatement $statement, array $bindings): static
    {
        foreach ($bindings as $key => $value) {
            $value = $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s.u') : $value;
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_resource($value) => PDO::PARAM_LOB,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                }
            );
        }
        return $this;
    }

    public function getAttribute(int $attribute): mixed
    {
        return $this->pdo->getAttribute($attribute);
    }

    public function setAttribute(int $attribute, mixed $value): static
    {
        $this->pdo->setAttribute($attribute, $value);
        return $this;
    }

    public function setReconnect(callable $reconnector): static
    {
        $this->reconnector = $reconnector;
        return $this;
    }

    public function lastInsertId(string $name): string|false
    {
        return $this->pdo->lastInsertId($name);
    }

    public function ping(): bool
    {
        try {
            $this->select('SELECT 1');
        } catch (PDOException) {
            return false;
        }
        return true;
    }

    public function reconnect(): mixed
    {
        return call_user_func($this->reconnector, $this);
    }

    public function disconnect(): static
    {
        $this->pdo = null;
        return $this;
    }
}