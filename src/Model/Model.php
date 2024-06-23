<?php

namespace Penguin\Component\Database\Model;

use Penguin\Component\Database\Model\Traits\HidesAttributes;
use Penguin\Component\Database\Model\Traits\HasAttributes;
use Penguin\Component\Database\Model\Traits\HasScopes;
use Penguin\Component\Database\Model\Traits\HasEvents;
use Penguin\Component\Database\Query\BuilderFactory;
use Penguin\Component\Collection\Arr;
use ReflectionAttribute;
use IteratorAggregate;
use JsonSerializable;
use ReflectionObject;
use ReflectionMethod;
use ArrayIterator;
use Traversable;

class Model implements IteratorAggregate, JsonSerializable
{
    use HidesAttributes;
    use HasAttributes;
    use HasScopes;
    use HasEvents;

    /**
     * The name of the "created at" column.
     */
    public const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     */
    public const UPDATED_AT = 'updated_at';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    protected bool $incrementing = true;

    /**
     * The connection name for the model.
     */
    protected ?string $connection = null;

    /**
     * The table associated with the model.
     */
    protected string $table;

    /**
     * The primary key for the table
     */
    protected ?string $primaryKey = null;

    protected static array $booted = [];

    public function __construct()
    {
        $this->bootIfNotBooted();
    }

    protected function bootIfNotBooted()
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            $this->registerAccessors();
            $this->registerMutators();
            $this->registerLocalScopes();
            $this->registerGlobalScopes();
            static::booting();
            static::boot();
            static::booted();
        }
    }

    protected static function booting(): void {}

    protected static function boot(): void {}

    protected static function booted(): void {}

    public function incrementing(): bool
    {
        return $this->incrementing;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function setTable(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function getKeyName(): ?string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->getOriginal($this->primaryKey);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->attributes);
    }

    public function jsonSerialize(): ?array
    {
        return Arr::except(array_diff($this->original, $this->visible), ...$this->hidden);
    }

    public function newModelQuery(): Builder
    {
        $builder = new Builder(service(BuilderFactory::class)->make());
        if (!empty($this->connection)) {
            $builder->connect($this->connection);
        }
        return $builder->from($this->table)->setModel($this);
    }

    public function newCollection(array $models = []): Collection
    {
        return new Collection($models);
    }

    public function save(): bool
    {
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        $query = $this->newModelQuery();

        if (!empty($this->getKey())) {
            $saved = $this->performUpdate($query);
        } else {
            $saved = $this->performInsert($query);
        }

        if ($saved) {
            $this->fireModelEvent('saved');
        }

        return $saved;
    }

    public function update(array $fields): bool
    {
        return $this->setAttributes($fields)->save();
    }

    public function delete(): bool|null
    {
        if (is_null($this->getKeyName())) {
            throw new LogicException('No primary key defined on model.');
        }

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        $query = $this->newModelQuery();
        $deleted = (int) $query->whereKey($this->getKey())->delete();

        if ($deleted) {
            $this->fireModelEvent('deleted');
        }
        return $deleted;
    }

    public function isDirty(): bool
    {
        foreach ($this->getAttribute() as $field => $value) {
            if (!isset($this->original[$field])) {
                return true;
            }

            if ($value != $this->original[$field]) {
                return true;
            }
        }
        return false;
    }

    public function newModel(array $fields): static
    {
        return (new static())->setAttributes($fields);
    }

    protected function performUpdate(Builder $query): bool
    {
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        $updated = (int) $query->whereKey($this->getKey())->update($this->getAttribute());

        if ($updated) {
            $this->fireModelEvent('updated');
        }

        return $updated;
    }

    protected function performInsert(Builder $query): bool
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        if ($this->incrementing()) {
            $inserted = $this->insertAndSetId($query);
        } else {
            $inserted = (bool) $query->insert($this->getAttributes());
            $this->originals = $this->getAttributes();
        }

        if ($inserted) {
            $this->fireModelEvent('created');
        }
        return $inserted;
    }

    protected function insertAndSetId(Builder $query): bool
    {
        $key = $query->insertGetId($this->getAttributes());
        if ($key > 0) {
            $this->attributes[$this->primaryKey] = $key;
            $this->originals = $this->getAttributes();
            return true;
        }
        return false;
    }

    protected function getReflectionObject(): ReflectionObject
    {
        return new ReflectionObject($this);
    }

    protected function getObjectAttributes(?string $attribute = null): array
    {
        $attributes = $this->getReflectionObject()->getAttributes($attribute);
        return array_map(fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $attributes);
    }

    protected function getReflectionMethodsByAttribute(string $attribute): ?array
    {
        $methods = $this->getReflectionObject()->getMethods();
        return array_filter($methods, function ($method) use ($attribute) {
            return !empty($method->getAttributes($attribute));
        });
    }

    protected function getClassAttributes(?string $attribute = null): ?array
    {
        return $this->getReflectionObject()->getAttributes($attribute);
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->newModelQuery()->{$method}(...$parameters);
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        return (new static())->{$method}(...$parameters);
    }
}