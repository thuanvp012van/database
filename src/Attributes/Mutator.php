<?php

namespace Penguin\Component\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Mutator
{
    public function __construct(protected string $attribute) {}

    public function __toString(): string
    {
        return $this->attribute;
    }
}