<?php

declare(strict_types=1);

namespace Laravolt\Avatar\Concerns;

trait AttributeGetter
{
    public function getAttribute($key)
    {
        return $this->$key;
    }

    /**
     * Get background color
     */
    public function getBackground(): string
    {
        return $this->background;
    }

    /**
     * Get foreground color
     */
    public function getForeground(): string
    {
        return $this->foreground;
    }

    /**
     * Get shape
     */
    public function getShape(): string
    {
        return $this->shape;
    }
}
