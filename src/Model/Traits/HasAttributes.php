<?php

namespace Penguin\Component\Database\Model\Traits;

use Penguin\Component\Event\EventDispatcherInterface;
use Penguin\Component\Event\ListenerProviderInterface;
use Penguin\Component\Database\Attributes\Accessor;
use Penguin\Component\Database\Attributes\Mutator;

trait HasAttributes
{
    protected array $appends = [];

    protected array $original = [];

    protected array $attributes = [];

    protected static array $accessors = [];

    protected static array $mutators = [];

    protected function registerAccessors(): void
    {
        $attribute = Accessor::class;
        $methods = $this->getReflectionMethodsByAttribute($attribute);
        foreach ($methods as $method) {
            $reflectionAttribute = $method->getAttributes($attribute)[0];
            $accessor = (string)$reflectionAttribute->newInstance();
            static::$accessors[$accessor] = $method->getName();
        }
    }

    protected function registerMutators()
    {
        $attribute = Mutator::class;
        $methods = $this->getReflectionMethodsByAttribute($attribute);
        foreach ($methods as $method) {
            $reflectionAttribute = $method->getAttributes($attribute)[0];
            $mutator = (string)$reflectionAttribute->newInstance();
            static::$mutators[$mutator] = $method->getName();
        }
    }

    public function hasMutator(?string $field = null): bool
    {
        return is_null($field) ? !empty(static::$mutators) : isset(static::$mutators[$field]);
    }

    public function hasAccessor(?string $field = null): bool
    {
        return is_null($field) ? !empty(static::$accessors) : isset(static::$accessors[$field]);
    }

    public function getKey(): mixed
    {
        return $this->original[$this->primaryKey];
    }

    public function setAttributes(array $fields): static
    {
        foreach ($fields as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        if ($this->hasMutator($key)) {
            $method = static::$mutators[$key];
            $value = $this->{$method}($value);
        }
        $this->attributes[$key] = $value;
        return $this;
    }

    public function setData(array $data): static
    {
        $this->original = $data;
        $this->attributes = $data;
        return $this;
    }

    public function getOriginal(?string $field = null): mixed
    {
        return isset($this->original[$field]) ? $this->original[$field] : null;
    }

    public function getOriginals(): array
    {
        return $this->originals;
    }

    public function getAttribute(string $key = null): mixed
    {
        if ($this->hasAccessor($key)) {
            $method = static::$accessors[$key];
            return $this->{$method}($this->attributes[$key]);
        }
        return $this->attributes[$key];
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }
}