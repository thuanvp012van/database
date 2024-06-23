<?php

namespace Penguin\Component\Database\Model;

use Penguin\Component\Database\Query\Builder as QueryBuilder;
use Penguin\Component\Database\Query\Traits\BuildsQueries;
use Penguin\Component\Database\Attributes\GlobalScope;
use Penguin\Component\Container\BoundMethod;
use Penguin\Component\Database\Scope;
use ReflectionObject;

class Builder
{
    use BuildsQueries;

    protected ?Model $model = null;

    protected array $widthoutGlobalSopes = [];

    public function __construct(protected QueryBuilder $query)
    {
    }

    public function setModel(Model $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function get(string ...$columns): Collection
    {
        $results = $this->applyGlobalScopes()->get(...$columns)->all();
        return $this->model->newCollection(
            array_map([$this, 'fetchToModel'], $results)
        );
    }

    public function find(mixed $id, array|string $columns = null): ?Model
    {
        return $this->whereKey($id)->get()->first();
    }

    public function whereKey(mixed $id): static
    {
        $this->query->where($this->model->getKeyName(), $id);
        return $this;
    }

    public function withoutGlobalScope(string $globalScope): static
    {
        if (!in_array($globalScope, $this->widthoutGlobalSopes)) {
            $this->widthoutGlobalSopes[] = $globalScope;
        }
        return $this;
    }

    public function withoutGlobalScopes(string ...$globalScopes): static
    {
        foreach ($globalScopes as $globalScope) {
            $this->withoutGlobalScope($globalScope);
        }
        return $this;
    }

    protected function applyGlobalScopes(): QueryBuilder
    {
        $scopes = array_diff($this->model->getGlobalScopes(), $this->widthoutGlobalSopes);
        foreach ($scopes as $scope) {
            if (isset(class_implements($scope)[Scope::class])) {
                BoundMethod::call([$scope, 'apply'], [$this, $this->model]);
            }
        }
        return $this->query;
    }

    public function create(array $fields): Model|false
    {
        $model = $this->model->newModel($fields);
        $saved = $model->save();
        if ($saved) {
            return $model;
        }
        return false;
    }

    protected function fetchToModel(array $fields): Model
    {
        $model = get_class($this->model);
        return (new $model())->setData($fields);
    }

    public function toSql(): string
    {
        return $this->applyGlobalScopes()->toSql();
    }

    public function toRawSql(): string
    {
        return $this->applyGlobalScopes()->toRawSql();
    }

    public function __call(string $method, array $arguments): mixed
    {
        if ($this->model?->hasLocalScope($method)) {
            $method = 'scope' . ucfirst($method);
            $this->model->{$method}($this, ...$arguments);
            return $this;
        }

        $result = $this->query->{$method}(...$arguments);
        if ($result === $this->query) {
            return $this;
        }
        return $result;
    }
}