<?php

declare(strict_types=1);

namespace Laravolt\Avatar\Concerns;

trait AttributeSetter
{
    public function setTheme($theme)
    {
        if (is_string($theme) || is_array($theme)) {
            if (is_string($theme) && !array_key_exists($theme, $this->themes)) {
                return $this;
            }

            $this->theme = $theme;
        }

        $this->initTheme();

        return $this;
    }

    public function setBackground($hex)
    {
        $this->background = $hex;

        return $this;
    }

    public function setForeground($hex)
    {
        $this->foreground = $hex;

        return $this;
    }

    public function setDimension($width, $height = null)
    {
        if (! $height) {
            $height = $width;
        }
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function setFontSize($size)
    {
        $this->fontSize = $size;

        return $this;
    }

    public function setFontFamily($font)
    {
        $this->fontFamily = $font;

        return $this;
    }

    public function setBorder($size, $color, $radius = 0)
    {
        $this->borderSize = $size;
        $this->borderColor = $color;
        $this->borderRadius = $radius;

        return $this;
    }

    public function setBorderRadius($radius)
    {
        $this->borderRadius = $radius;

        return $this;
    }

    public function setShape($shape)
    {
        $this->shape = $shape;

        return $this;
    }

    public function setChars($chars)
    {
        $this->chars = $chars;

        return $this;
    }

    public function setFont($font)
    {
        if (is_file($font)) {
            $this->font = $font;
        }

        return $this;
    }
}
