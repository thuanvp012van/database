<?php

namespace Penguin\Component\Database\Query;

class QueryRaw
{
    public function __construct(public readonly string $sql, public readonly array $bindings = []) {}
}