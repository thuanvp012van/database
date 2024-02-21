<?php

namespace Penguin\Component\Database;

use Swoole\Coroutine\Channel;
use Closure;

class Pool implements PoolInterface
{
    protected ?Channel $channel;

    public function __construct(protected Closure $connector, protected readonly int $minSize = 1, protected readonly int $maxSize = 64)
    {
        $this->channel = new Channel($maxSize);
    }

    // public function set

    public function fill(): void
    {
        while ($this->maxSize > $this->channel->length()) {
            $this->make();
        }
    }

    public function get(float $timeout = -1): ConnectionInterface|false
    {
        if (is_null($this->channel)) {
            throw new \RuntimeException('Pool has been closed');
        }
        return $this->channel->pop($timeout);
    }

    public function release(ConnectionInterface $connection): void
    {
        if (is_null($this->channel)) {
            return;
        }
        if ($connection !== null) {
            $this->channel->push($connection);
        } else {// connection broken
            $this->make();
        }
    }

    public function isFull(): bool
    {
        return $this->channel->isFull();
    }

    public function isEmpty(): bool
    {
        return $this->channel->isEmpty();
    }

    protected function make(): void
    {
        $this->release(call_user_func([$this, 'connector']));
    }

    public function close(): void
    {
        $this->channel->close();
        $this->channel = null;
    }
}