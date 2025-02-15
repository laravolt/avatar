<?php

declare(strict_types=1);

namespace Laravolt\Avatar\Concerns;

trait AttributeSetter
{
    public function setTheme($theme): static
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

    public function setBackground($hex): static
    {
        $this->background = $hex;

        return $this;
    }

    public function setForeground($hex): static
    {
        $this->foreground = $hex;

        return $this;
    }

    public function setDimension($width, $height = null): static
    {
        if (! $height) {
            $height = $width;
        }
        $this->width = $width;
        $this->height = $height;

        return $this;
    }
    
    public function setResponsive($responsive): static
    {
        $this->responsive = $responsive;

        return $this;
    }

    public function setFontSize($size): static
    {
        $this->fontSize = $size;

        return $this;
    }

    public function setFontFamily($font): static
    {
        $this->fontFamily = $font;

        return $this;
    }

    public function setBorder($size, $color, $radius = 0): static
    {
        $this->borderSize = $size;
        $this->borderColor = $color;
        $this->borderRadius = $radius;

        return $this;
    }

    public function setBorderRadius($radius): static
    {
        $this->borderRadius = $radius;

        return $this;
    }

    public function setShape($shape): static
    {
        $this->shape = $shape;

        return $this;
    }

    public function setChars($chars): static
    {
        $this->chars = $chars;

        return $this;
    }

    public function setFont($font): static
    {
        if (is_file($font)) {
            $this->font = $font;
        }

        return $this;
    }
}
