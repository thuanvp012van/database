<?php

namespace Penguin\Component\Database;

use Penguin\Component\Database\Model\Builder;
use Penguin\Component\Database\Model\Model;

interface Scope
{
    public function apply(Builder $builder, Model $model): void;
}