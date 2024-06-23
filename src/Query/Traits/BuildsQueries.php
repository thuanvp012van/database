<?php

namespace Penguin\Component\Database\Query\Traits;

trait BuildsQueries
{
    public function first(string ...$columns): mixed
    {
        return $this->limit(1)->get(...$columns)->first();
    }
}