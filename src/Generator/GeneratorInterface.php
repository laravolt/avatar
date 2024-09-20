<?php

namespace Laravolt\Avatar\Generator;

interface GeneratorInterface
{
    public function make(?string $name, int $length, bool $uppercase, bool $ascii, bool $rtl): string;
}
