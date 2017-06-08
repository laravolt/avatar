<?php

namespace Laravolt\Avatar\Facades;
use Illuminate\Support\Facades\Facade;

class Avatar extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'avatar';
    }
}
