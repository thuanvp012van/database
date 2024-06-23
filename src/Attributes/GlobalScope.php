<?php

namespace Penguin\Component\Database\Attributes;

use Penguin\Component\Database\Scope;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class GlobalScope
{
    public readonly array $scopes;

    public function __construct(string ...$scopes)
    {
        $this->scopes = $scopes;
    }
}