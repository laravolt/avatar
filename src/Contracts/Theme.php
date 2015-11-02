<?php
namespace Laravolt\Avatar\Contracts;

interface Theme
{
    public function __construct($string);

    public function getText();

    public function getBackground();

    public function getForeground();
}
