<?php

namespace Laravolt\Avatar\Generator;

interface GeneratorInterface
{
    public function make($name, $length, $uppercase, $ascii);
}
