<?php

namespace Penguin\Component\Database\Query;

use Penguin\Component\Database\ConnectionInterface;
use Penguin\Component\Database\Query\QueryRaw;
use Penguin\Component\Database\PoolInterface;
use DateTimeInterface;
use Generator;

class Builder
{
    protected array $bindings = [
        'select' => [],
        'insert' => [],
        'update' => [],
        'delete' => [],
        'join' => [],
        'condition' => []
    ];

    protected array $fields = [];

    protected string $table;

    protected string $alias = '';

    protected array $segments = [
        'insert' => false,
        'update' => false,
        'delete' => false,
        'select' => true,
        'join' => false,
        'condition' => false,
        'groupBy' => false,
        'having' => false,
        'orderBy' => false,
        'limit' => false,
        'offset' => false,
        'union' => false,
    ];

    protected array $conditions = [];

    protected array $orderBy = [];

    protected array $joinTables = [];

    protected array $stackGroupConditions = [];

    protected array $groupColumns = [];

    protected ?int $limit;

    protected ?int $offset;

    public function __construct(protected PoolInterface $pool)
    {
    }

    public function from(string $table, string $alias = null): static
    {
        if ($alias) {
            $this->alias = $alias;
        }
        $this->table = $table;
        return $this;
    }

    public function alias(string $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    public function find(string $id, string $primaryKey = 'id'): array
    {
        $this->conditions = [];
        return $this->where($primaryKey, $id)->first();
    }

    public function select(string|QueryRaw ...$columns): static
    {
        foreach ($columns as $column) {
            $this->fields[] = $column;
            if ($column instanceof QueryRaw) {
                $this->addBindValues($column->bindings, 'select');
            }
        }
        return $this;
    }

    public function selectRaw(string $query, array $bindings = []): static
    {
        $queryRaw = new QueryRaw($query);
        $this->fields = [$queryRaw];
        $this->addBindValues($bindings, 'select');
        return $this;
    }

    public function insert(array $fields): int
    {
        $this->fields = $fields;
        $this->addBindValues($fields, 'insert');
        return $this->run('insert', function (ConnectionInterface $connection) {
            return $connection->insert($this->toSql(), $this->getBindings());
        });
    }

    public function insertOrIgnore(array $fields): int
    {
        $this->fields = $fields;
        $this->addBindValues($fields, 'insert');
        return $this->run('insert', function (ConnectionInterface $connection) {
            $sql = $this->toSql();
            $insert = 'INSERT';
            $position = strpos($sql, $insert);
            if ($position !== false) {
                $sql = substr_replace($sql, 'INSERT IGNORE', $position, strlen($insert));
            }
            return $connection->insert($sql, $this->getBindings());
        });
    }

    public function insertGetId(array $fields, string $primaryKey = 'id'): int
    {
        $this->fields = $fields;
        $this->addBindValues($fields, 'insert');
        return $this->run('insert', function (ConnectionInterface $connection) use ($primaryKey) {
            return $connection->insertGetId($this->toSql(), $primaryKey, $this->getBindings());
        });
    }

    public function insertUsing(array $columns, Builder $query)
    {
        
    }

    public function delete(): int
    {
        return $this->run('delete', function (ConnectionInterface $connection) {
            return $connection->delete($this->toSql(), $this->getBindings());
        });
    }

    public function update(array $fields): int
    {
        $this->fields = $fields;
        foreach ($fields as $value) {
            if ($value instanceof QueryRaw) {
                $this->addBindValues($value->bindings, 'update');
                continue;
            }
            $this->addBindValue($value, 'update');
        }
        return $this->run('update', function (ConnectionInterface $connection) {
            return $connection->update($this->toSql(), $this->getBindings());
        });
    }

    public function get()
    {
        return $this->run('select', function (ConnectionInterface $connection) {
            return $connection->select($this->toSql(), $this->getBindings());
        });
    }

    public function getArray(): array
    {
        return $this->run('select', function (ConnectionInterface $connection) {
            return $connection->select($this->toSql(), $this->getBindings());
        });
    }

    public function first(): mixed
    {
        $this->limit(1);
        return $this->run('select', function (ConnectionInterface $connection) {
            $result = $connection->select($this->toSql(), $this->getBindings());
            return !empty($result) ? $result[0] : null;
        });
    }

    public function limit(int $limit, int $offset = null): static
    {
        $this->segments['limit'] = true;
        $this->limit = $limit;
        if ($offset !== null) {
            $this->offset($offset);
        }
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->segments['offset'] = true;
        $this->offset = $offset;
        return $this;
    }

    public function groupBy(string ...$groupColumn): static
    {
        $this->segments['groupBy'] = true;
        $this->groupColumns = array_merge($this->groupColumns, $groupColumn);
        return $this;
    }

    public function orderBy(string $column, string $order = 'ASC'): static
    {
        $this->segments['orderBy'] = true;
        $this->orderBy[$column] = $order;
        return $this;
    }

    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public function reorder(string $column, string $order = 'ASC'): static
    {
        $this->orderBy = [];
        return $this->orderBy($column, $order);
    }

    public function count(string $column = null): int
    {
        if (is_null($column)) {
            $column = 1;
        } else {
            $column = $this->getColumn($column);
        }
        return array_values($this->selectRaw("COUNT($column)")->first())[0];
    }

    public function max(string $column): int
    {
        return array_values($this->selectRaw("MAX($column)")->first())[0];
    }

    public function min(string $column): int
    {
        return array_values($this->selectRaw("MIN($column)")->first())[0];
    }

    public function avg(string $column): int
    {
        return array_values($this->selectRaw("AVG($column)")->first())[0];
    }

    public function sum(string $column): int
    {
        return array_values($this->selectRaw("SUM($column)")->first())[0];
    }

    public function exists(): bool
    {
        return !empty($this->first());
    }

    public function doesntExist(): bool
    {
        return empty($this->first());
    }

    public function cursor(): Generator
    {
        return $this->run('select', function (ConnectionInterface $connection) {
            $statement = $connection->statement($this->toSql(), $this->getBindings());
            while ($row = $statement->fetch()) {
                yield $row;
            }
        });
    }

    public function where(string|array|callable $columns, mixed $value = null, string $condition = '=', string $logicalOperator = 'AND'): static
    {
        $this->segments['condition'] = true;

        if (is_array($columns)) {
            return $this->addNestedCondition(function () use ($columns, $logicalOperator) {
                foreach ($columns as $column => $value) {
                    if (is_array($value)) {
                        $this->addCondition($value[0], $value[1], $value[2], $logicalOperator);
                        continue;
                    }
                    $this->addCondition($column, '=', $value, $logicalOperator);
                }
            }, $logicalOperator);
        } else if (is_callable($columns)) {
            return $this->addNestedCondition($columns, $logicalOperator);
        }

        if (is_callable($value)) {
            $builder = new static($this->pool);
            $value($builder);
            $value = $builder;
        }

        return $this->addCondition($columns, $condition, $value, $logicalOperator);
    }

    public function orWhere(mixed $columns, mixed $value = null, string $condition = '=', string $logicalOperator = 'OR'): static
    {
        return $this->where($columns, $value, $condition, $logicalOperator);
    }

    public function whereNull(string $column): static
    {
        return $this->where($column, null, 'IS');
    }

    public function whereNot(mixed $columns, mixed $value = null, string $condition = '='): static
    {
        return $this->where($columns, $value, $condition, 'AND NOT');
    }

    public function orWhereNot(mixed $columns, mixed $value = null, string $condition = '='): static
    {
        return $this->where($columns, $value, $condition, 'OR NOT');
    }

    public function whereNotNull(string $column): static
    {
        return $this->where($column, null, 'IS NOT');
    }

    public function orWhereNull(string $column): static
    {
        return $this->orWhere($column, null, 'IS');
    }

    public function orWhereNotNull(string $column): static
    {
        return $this->orWhere($column, null, 'IS NOT');
    }

    public function whereDate(string $column, string|DateTimeInterface $value, string $condition = '='): static
    {
        $value = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
        return $this->whereRaw("DATE($column) $condition ?", [$value]);
    }

    public function whereMonth(string $column, string|DateTimeInterface $value, string $condition = '='): static
    {
        $value = $value instanceof DateTimeInterface ? $value->format('m') : $value;
        return $this->whereRaw("MONTH($column) $condition ?", [$value]);
    }

    public function whereDay(string $column, string|DateTimeInterface $value, string $condition = '='): static
    {
        $value = $value instanceof DateTimeInterface ? $value->format('d') : $value;
        return $this->whereRaw("DAY($column) $condition ?", [$value]);
    }

    public function whereYear(string $column, string|DateTimeInterface $value, string $condition = '='): static
    {
        $value = $value instanceof DateTimeInterface ? $value->format('Y') : $value;
        return $this->whereRaw("YEAR($column) $condition ?", [$value]);
    }

    public function whereTime(string $column, string|DateTimeInterface $value, string $condition = '='): static
    {
        $value = $value instanceof DateTimeInterface ? $value->format('H:i:s') : $value;
        return $this->whereRaw("TIME($column) $condition ?", [$value]);
    }

    public function whereIn(string $column, iterable|callable|Builder $items, bool $isNot = false): static
    {
        $logicalOperator = $isNot ? 'NOT IN' : 'IN';
        if (is_array($items)) {
            $placeholders = '?';
            foreach (array_slice($items, 1) as $_) {
                $placeholders .= ", ?";
            }
            return $this->whereRaw("$column $logicalOperator ($placeholders)", $items);
        }

        if (is_callable($items)) {
            $builder = new static($this->pool);
            $items($builder);
            $items = $builder;
        }

        return $this->whereRaw("$column $logicalOperator ({$items->toSql()})", $items->getBindings());
    }

    public function whereNotIn(string $column, array|callable|Builder $items): static
    {
        return $this->whereIn($column, $items, true);
    }

    public function whereColumn(string|array $first, string|array $second = null, string $operator = '=', string $logicalOperator = 'AND'): static
    {
        if (is_array($first)) {
            return $this->addNestedCondition(function () use ($first) {
                foreach ($first as $key => $value) {
                    if (is_array($value)) {
                        if (count($value) === 3) {
                            $this->whereRaw("{$this->getColumn($value[0])} {$value[1]} {$this->getColumn($value[2])}");
                            continue;
                        }
                        $this->whereRaw("{$this->getColumn($value[0])} = {$this->getColumn($value[1])}");
                        continue;
                    }

                    $this->whereRaw("{$this->getColumn($key)} = {$this->getColumn($value)}");
                }
            }, $logicalOperator);
        }
        return $this->whereRaw("{$this->getColumn($first)} $operator {$this->getColumn($second)}");
    }

    public function whereExists(callable|Builder $callback): static
    {
        if (is_callable($callback)) {
            $builder = new static($this->pool);
            $callback($builder);
        } else {
            $builder = $callback;
        }
        return $this->whereRaw("EXISTS ({$builder->toSql()})", $builder->getBindings());
    }

    public function whereRaw(string $query, array $bindings = []): static
    {
        $this->segments['condition'] = true;
        $queryRaw = new QueryRaw($query, $bindings);
        $this->addConditionRaw($queryRaw);
        return $this;
    }

    public function join(string $table, string $primaryKey, string $foreignKey, string $condition = ''): static
    {
        $this->joinTables[$table] = ['INNER JOIN', $this->getColumn($primaryKey), $this->getColumn($foreignKey), $condition];
        return $this;
    }

    public function leftJoin(string $table, string $primaryKey, string $foreignKey, string $condition = ''): static
    {
        $this->joinTables[$table] = ['LEFT JOIN', $this->getColumn($primaryKey), $this->getColumn($foreignKey), $condition];
        return $this;
    }

    public function rightJoin(string $table, string $primaryKey, string $foreignKey, string $condition = ''): static
    {
        $this->joinTables[$table] = ['LEFT JOIN', $this->getColumn($primaryKey), $this->getColumn($foreignKey), $condition];
        return $this;
    }

    public function increment(string|array $column, int|float $value = 1, array $fields = []): int
    {
        $column = $this->getColumn($column);
        return $this->update([new QueryRaw("$column = $column + ?", [$value]), ...$fields]);
    }

    public function incrementEach(array $fields): int
    {
        $updateFields = [];
        foreach ($fields as $column => $value) {
            $column = $this->getColumn($column);
            $updateFields[] = new QueryRaw("$column = $column + ?", [$value]);
        }
        return $this->update($updateFields);
    }

    public function decrement(string|array $column, int|float $value = 1, array $fields = []): int
    {
        $column = $this->getColumn($column);
        return $this->update([new QueryRaw("$column = $column - ?", [$value]), ...$fields]);
    }

    public function decrementEach(array $fields): int
    {
        $updateFields = [];
        foreach ($fields as $column => $value) {
            $column = $this->getColumn($column);
            $updateFields[] = new QueryRaw("$column = $column - ?", [$value]);
        }
        return $this->update($updateFields);
    }

    public function truncate(): int
    {
        return $this->run('insert', function (ConnectionInterface $connection) {
            return $connection->statement("TRUNCATE TABLE {$this->getTable()}")->rowCount();
        });
    }

    protected function addConditionRaw(QueryRaw $queryRaw, string $logicalOperator = 'AND'): void
    {
        if (!empty($this->stackGroupConditions)) {
            $conditionPointer = null;
            foreach (array_keys($this->stackGroupConditions) as $stackName) {
                if (is_null($conditionPointer)) {
                    $conditionPointer = &$this->conditions[$stackName];
                } else {
                    $conditionPointer = &$conditionPointer[$stackName];
                }
            }
            $currentStack = array_key_last($this->stackGroupConditions);
            if (empty($conditionPointer)) {
                $conditionPointer['nested_cond'] = $this->stackGroupConditions[$currentStack];
                $whereRaw = $queryRaw->sql;
            } else {
                $whereRaw = " $logicalOperator {$queryRaw->sql}";
            }
            $conditionPointer[] = $whereRaw;
            $this->addBindValues($queryRaw->bindings, 'condition');
        } else {
            if (!empty($this->conditions)) {
                $whereRaw = " $logicalOperator {$queryRaw->sql}";
            } else {
                $whereRaw = $queryRaw->sql;
            }
            $this->conditions[] = $whereRaw;
            $this->addBindValues($queryRaw->bindings, 'condition');
        }
    }

    protected function addCondition(string $column, string $condition, mixed $value, string $logicalOperator = 'AND'): static
    {
        if (!empty($this->stackGroupConditions)) {
            $conditionPointer = null;
            foreach (array_keys($this->stackGroupConditions) as $stackName) {
                if (is_null($conditionPointer)) {
                    $conditionPointer = &$this->conditions[$stackName];
                } else {
                    $conditionPointer = &$conditionPointer[$stackName];
                }
            }
            $currentStack = array_key_last($this->stackGroupConditions);
            if (empty($conditionPointer)) {
                $conditionPointer['nested_cond'] = $this->stackGroupConditions[$currentStack];
                if (str_contains($logicalOperator, 'NOT')) {
                    $logicalOperator = 'NOT ';
                } else {
                    $logicalOperator = null;
                }
            } else {
                $logicalOperator = " $logicalOperator ";
            }
            $column = "$logicalOperator{$this->getColumn($column)}";
            $conditionPointer[] = [$column, $condition, ($value instanceof Builder ? "({$value->toSql()})" : '?')];
            $this->addBindValue($value, 'condition');
        } else {
            if (!empty($this->conditions)) {
                $logicalOperator = " $logicalOperator ";
            } else {
                if (str_contains($logicalOperator, 'NOT')) {
                    $logicalOperator = 'NOT ';
                } else {
                    $logicalOperator = null;
                }
            }
            $column = "$logicalOperator{$this->getColumn($column)}";
            $this->conditions[] = [$column, $condition, $value instanceof Builder ? "({$value->toSql()})" : '?'];
            $this->addBindValue($value, 'condition');
        }
        return $this;
    }

    protected function addNestedCondition(callable $callback, string $logicalOperator = 'AND'): static
    {
        if (empty($this->conditions)) {
            if (str_contains($logicalOperator, 'NOT')) {
                $logicalOperator = 'NOT ';
            } else {
                $logicalOperator = null;
            }
        } else {
            $logicalOperator = " $logicalOperator ";
        }
        $this->stackGroupConditions['groupCondition' . count($this->conditions)] = "$logicalOperator(%s)";
        $callback($this);
        array_pop($this->stackGroupConditions);
        return $this;
    }

    protected function run(string $typeDML, callable $callback): mixed
    {
        $this->setTypeDML($typeDML);
        $useReadPool = false;
        if ($this->segments['select']) {
            $useReadPool = true;
        }
        $connection = $this->pool->get($useReadPool);
        $results = $callback($connection);
        $this->pool->put($connection, $useReadPool);
        return $results;
    }

    protected function buildSelect(): string
    {
        if (empty($this->fields)) {
            $fields = '*';
        } else {
            $fields = join(', ', $this->backtick($this->fields));
        }
        return "SELECT {$fields} FROM {$this->getTable()}";
    }

    protected function buildInsert(): string
    {
        $fields = array_keys($this->fields);
        $columns = join(', ', $this->backtick($fields));
        $fields = join(', ', array_map(function () {
            return '?';
        }, $fields));

        $sql = "INSERT INTO {$this->getTable()} ($columns) VALUES ($fields)";
        return $sql;
    }

    protected function buildUpdate(): string
    {
        $sets = '';
        foreach ($this->fields as $key => $value) {
            if (empty($sets)) {
                $sets .= $value instanceof QueryRaw ? $value->sql : "{$this->getColumn($key)} = ?";
            } else {
                $sets .= $value instanceof QueryRaw ? ", {$value->sql}" : ", {$this->getColumn($key)} = ?";
            }
        }
        return "UPDATE {$this->getTable()} SET $sets";
    }

    protected function buildDelete(): string
    {
        return "DELETE FROM {$this->getTable()}";
    }

    protected function buildCondition(array $conditions = [], string $prefix = 'WHERE '): string
    {
        if (empty($conditions)) {
            $conditions = $this->conditions;
        }
        if (!empty($conditions)) {
            $command = '';
            $nestedCond = empty($conditions['nested_cond']) ? '%s' : $conditions['nested_cond'];
            foreach ($conditions as $key => $value) {
                if (is_int($key)) {
                    if (is_array($value)) {
                        $command .= "{$value[0]} {$value[1]} {$value[2]}";
                    } else {
                        $command .= $value;
                    }
                } else if ($key !== 'nested_cond') {
                    $command .= $this->buildCondition($value, '');
                }
            }
            return !empty($command) ? sprintf($nestedCond, "$prefix$command") : null;
        }
        return null;
    }

    protected function buildLimit(): string
    {
        return "LIMIT {$this->limit}";
    }

    protected function buildOffset(): string
    {
        return "OFFSET {$this->offset}";
    }

    protected function buildGroupBy(): string
    {
        $groupColumns = join(', ', $this->backtick($this->groupColumns));
        return "GROUP BY $groupColumns";
    }

    protected function buildOrderBy(): string
    {
        $sql = 'ORDER BY ';
        $firstKey = array_key_first($this->orderBy);
        foreach ($this->orderBy as $column => $order) {
            if ($column === $firstKey) {
                $sql .= "{$this->getColumn($column)} $order";
                continue;
            }
            $sql .= ", {$this->getColumn($column)} $order";
        }
        return $sql;
    }

    protected function addBindValue(mixed $value, string $fieldName): void
    {
        if ($value instanceof Builder) {
            $this->bindings[$fieldName] = array_merge($this->bindings[$fieldName], $value->getBindings());
            return;
        }
        $this->bindings[$fieldName][] = $value;
    }

    protected function addBindValues(array $fields, string $fieldName): void
    {
        foreach ($fields as $value) {
            if (is_array($value)) {
                $this->addBindValues($value, $fieldName);
                continue;
            }
            $this->addBindValue($value, $fieldName);
        }
    }

    public function getBindings(): array
    {
        $bindings = [];
        foreach ($this->bindings as $value) {
            if (!empty($value)) {
                array_push($bindings, ...$value);
            }
        }
        return $bindings;
    }

    protected function setTypeDML(string $type): void
    {
        $this->segments['insert'] = false;
        $this->segments['update'] = false;
        $this->segments['delete'] = false;
        $this->segments['select'] = false;
        $this->segments[$type] = true;
    }

    protected function backtick(array $items): array
    {
        return array_map([$this, 'getColumn'], $items);
    }

    protected function getTable(): string
    {
        if (!empty($this->alias)) {
            return "`{$this->table}` AS `{$this->alias}`";
        }

        return "`{$this->table}`";
    }

    protected function getColumn(string|QueryRaw $column): string
    {
        if ($column instanceof QueryRaw) {
            return $column->sql;
        }
        if (str_contains($column, '.')) {
            return "`$column`";
        }
        if (!empty($this->alias)) {
            return "`{$this->alias}`.`$column`";
        }
        return "`{$this->table}`.`$column`";
    }

    public function toSql(): string
    {
        $segments = array_filter($this->segments);
        foreach ($segments as $key => &$segment) {
            $key = ucfirst($key);
            $segment = call_user_func([$this, "build{$key}"]);
        }
        unset($segment);
        return join(' ', $segments);
    }

    public function toRawSql(): string
    {
        $sql = $this->toSql();
        $bindings = $this->getBindings();
        foreach ($bindings as $value) {
            if (!is_resource($value)) {
                $position = strpos($sql, '?');
                if ($position !== false) {
                    if (!is_int($value) && !is_float($value)) {
                        $value = (string)$value;
                        $value = "'$value'";
                    }
                    $sql = substr_replace($sql, $value, $position, 1);
                }
            }
        }
        return $sql;
    }

    public function explain(): array
    {
        return $this->run('select', function (ConnectionInterface $connection) {
            return $connection->select("EXPLAIN {$this->toSql()}", $this->getBindings());
        });
    }
}
