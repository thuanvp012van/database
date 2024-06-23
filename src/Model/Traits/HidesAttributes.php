<?php

namespace Penguin\Component\Database\Model\Traits;

trait HidesAttributes
{
    protected array $hidden = [];

    protected array $visible = [];

    public function getHidden(): array
    {
        return $this->hidden;
    }

    public function setHidden(array $hidden): static
    {
        $this->hidden = $hidden;
        return $this;
    }

    public function addHidden(array $hidden): static
    {
        foreach ($hidden as $field) {
            if (!in_array($field, $this->hidden)) {
                $this->hidden[] = $field;
            }
        }

        return $this;
    }

    public function getVisible(): array
    {
        return $this->visible;
    }

    public function setVisible(array $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function addVisible(array $visible): static
    {
        foreach ($visible as $field) {
            if (!in_array($field, $this->visible)) {
                $this->visible[] = $field;
            }
        }

        return $this;
    }
}