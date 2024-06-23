<?php

namespace Penguin\Component\Database\Model;

use Penguin\Component\Database\Model\Builder;
use Penguin\Component\Database\Model\Model;
use Penguin\Component\Database\Scope;

class DeletedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('deleted_at', '2024-05-23 04:33:41', '!=');
    }
}