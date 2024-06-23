<?php

namespace Penguin\Component\Database\Model\Traits;

use Penguin\Component\Database\Attributes\GlobalScope;
use Penguin\Component\Database\Attributes\LocalScope;
use Penguin\Component\Database\Model\Builder;
use ReflectionMethod;

trait HasScopes
{
    protected static array $localScopes = [];

    protected static array $globalScopes = [];

    protected function registerLocalScopes(): void
    {
        $methods = $this->getReflectionObject()->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (str_starts_with($methodName, 'scope')) {
                static::$localScopes[] = lcfirst(substr($methodName, 5));
            }
        }
    }

    protected function registerGlobalScopes(): void
    {
        $attributes = $this->getObjectAttributes(GlobalScope::class);
        foreach ($attributes as $attribute) {
            foreach ($attribute->scopes as $scope) {
                if (!in_array($scope, static::$globalScopes)) {
                    static::$globalScopes[] = $scope;
                }
            }
        }
    }

    public static function hasLocalScope(string $scope): bool
    {
        return in_array($scope, static::$localScopes);
    }

    public static function getLocalScopes(): array
    {
        return static::$localScopes;
    }

    public static function hasGlobalScope(string $scope): bool
    {
        return in_array($scope, static::$globalScopes);
    }

    public static function getGlobalScopes()
    {
        return static::$globalScopes;
    }
}