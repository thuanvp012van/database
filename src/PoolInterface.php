<?php

namespace Penguin\Component\Database;

interface PoolInterface
{
    public function fill(): void;

    public function get(): ConnectionInterface|false;

    public function release(ConnectionInterface $connection): void;

    public function isFull(): bool;

    public function isEmpty(): bool;

    public function close(): void;
}