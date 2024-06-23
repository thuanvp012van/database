<?php

namespace Penguin\Component\Database\Model;

use Penguin\Component\Collection\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public function toQuery()
    {
        $model = $this->first();
        if (!$model) {
            throw new \LogicException('Unable to create query for empty collection.');
        }

        return $model->newModelQuery()->whereIn(
            $model->getKeyName(),
            array_map(fn ($model) => $model->getKey(), $this->items)
        );
    }
}