<?php

declare(strict_types=1);

namespace Laravolt\Avatar\Concerns;

trait AttributeGetter
{
    public function getAttribute($key)
    {
        return $this->$key;
    }
}
