<?php

namespace Penguin\Component\Database\Model\Traits;

use Penguin\Component\Event\EventDispatcherInterface;
use Penguin\Component\Event\ListenerProviderInterface;
use Penguin\Component\Database\Attributes\Listen;
use Penguin\Component\Event\NullDispatcher;

trait HasEvents
{
    protected static ListenerProviderInterface $listenerProvider;

    protected static EventDispatcherInterface $eventDispatcher;

    public static function setListenerProvider(ListenerProviderInterface $listenerProvider): void
    {
        static::$listenerProvider = $listenerProvider;
    }
    
    public static function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        static::$eventDispatcher = $eventDispatcher;
    }

    public static function creating(callable $callback): void
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function created(callable $callback): void
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function updating(callable $callback): void
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function updated(callable $callback): void
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function saving(callable $callback): void
    {
        static::registerModelEvent(__FUNCTION__, $callback); 
    }

    public static function saved(callable $callback): void
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function deleting(callable $callback): void
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function deleted(callable $callback): void
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function withoutEvents(callable $callback): mixed
    {
        $dispatcher = static::$eventDispatcher;

        static::setEventDispatcher(new NullDispatcher(static::$listenerProvider));

        try {
            return $callback();            
        } finally {
            static::setEventDispatcher($dispatcher);
        }
    }

    public function saveQuietly(): bool
    {
        static::withoutEvents(function () {
            return $this->save();
        });
    }

    public function deleteQuietly(): bool|null
    {
        static::withoutEvents(function () {
            return $this->delete();
        });
    }

    protected function fireModelEvent(string $event): array|null|false
    {
        $class = static::class;
        return static::$eventDispatcher->dispatch("model:$class.$event", [$this]);
    }

    protected static function registerModelEvent(string $event, callable $callback): void
    {
        $class = static::class;
        static::$listenerProvider->addListener("model:$class.$event", $callback);
    }
}