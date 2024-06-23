<?php

namespace Penguin\Component\Database\Query;

class JoinClause extends Builder
{
    public function on(string $firtst, string $second, string $condition = '='): static
    {
        return $this->whereColumn($firtst, $second, $condition);
    }

    public function orOn(string $firtst, string $second, string $condition = '='): static
    {
        return $this->whereColumn($firtst, $second, $condition, 'or');
    }

    public function getBindings(): array
    {
        return $this->bindings['condition'];
    }

    protected function buildCondition(array $conditions = [], string $prefix = 'where '): string
    {
        return parent::buildCondition($conditions, 'on ');
    }

    public function toSql(): string
    {
        return "{$this->getTable()} {$this->buildCondition()}";
    }
}