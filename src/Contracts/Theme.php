<?php
namespace Laravolt\Avatar\Contracts;

interface Theme
{
    public function __construct($string);

    public function getInitials();

    public function getBackground();

    public function getForeground();

    public function getFont();
}
