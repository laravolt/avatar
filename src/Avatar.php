<?php

namespace Laravolt\Avatar;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Arr;
use Intervention\Image\Facades\Image;
use Laravolt\Avatar\Contracts\Theme;

class Avatar
{
    protected $shape;
    protected $name;
    protected $font;
    protected $fontSize;
    protected $width;
    protected $height;
    protected $image;
    protected $background = '#cccccc';
    protected $foreground = '#ffffff';
    protected $borderSize = 0;
    protected $borderColor;
    protected $initials = '';

    protected $cache;
    protected $theme;

    /**
     * Avatar constructor.
     *
     * @param array        $config
     * @param CacheManager $cache
     * @param Theme        $theme
     */
    public function __construct(array $config, CacheManager $cache, Theme $theme)
    {
        $this->shape = Arr::get($config, 'shape', 'circle');
        $this->fontSize = Arr::get($config, 'fontSize', 32);
        $this->width = Arr::get($config, 'width', 100);
        $this->height = Arr::get($config, 'height', 100);
        $this->borderSize = Arr::get($config, 'border.size');
        $this->borderColor = Arr::get($config, 'border.color');

        $this->cache = $cache;
        $this->theme = $theme;
    }

    public function create($name)
    {
        $this->theme->setText($name);
        $this->setInitials($this->theme->getInitials());
        $this->setBackground($this->theme->getBackground());
        $this->setForeground($this->theme->getForeground());
        $this->setFont($this->theme->getFont());

        return $this;
    }

    public function toBase64()
    {
        return $this->cache->rememberForever($this->cacheKey(), function () {
            $this->buildAvatar();

            return $this->image->encode('data-url');
        });
    }

    public function save($path, $quality = 90)
    {
        $this->buildAvatar();

        return $this->image->save($path, $quality);
    }

    public function setInitials($initials)
    {
        $this->initials = $initials;

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
        if (!$height) {
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

    public function setFont($font)
    {
        $this->font = $font;

        return $this;
    }

    public function setBorder($size, $color)
    {
        $this->borderSize = $size;
        $this->borderColor = $color;

        return $this;
    }

    public function setShape($shape)
    {
        $this->shape = $shape;

        return $this;
    }

    protected function getBorderColor()
    {
        if ($this->borderColor == 'foreground') {
            return $this->foreground;
        }
        if ($this->borderColor == 'background') {
            return $this->background;
        }

        return $this->borderColor;
    }

    protected function buildAvatar()
    {
        $x = $this->width / 2;
        $y = $this->height / 2;

        $this->image = Image::canvas($this->width, $this->height);

        $this->createShape();

        $this->image->text($this->initials, $x, $y, function ($font) {
            $font->file($this->theme->getFont());
            $font->size($this->fontSize);
            $font->color($this->foreground);
            $font->align('center');
            $font->valign('middle');
        });
    }

    protected function createShape()
    {
        $method = 'create'.ucfirst($this->shape).'Shape';
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \InvalidArgumentException("Shape [$this->shape] currently not supported.");
    }

    protected function createCircleShape()
    {
        $circleDiameter = $this->width - $this->borderSize;
        $x = $this->width / 2;
        $y = $this->height / 2;

        $this->image->circle($circleDiameter, $x, $y, function ($draw) {
            $draw->background($this->background);
            $draw->border($this->borderSize, $this->getBorderColor());
        });
    }

    protected function createSquareShape()
    {
        $x = $y = $this->borderSize;
        $width = $this->width - ($this->borderSize * 2);
        $height = $this->height - ($this->borderSize * 2);
        $this->image->rectangle($x, $y, $width, $height, function ($draw) {
            $draw->background($this->background);
            $draw->border($this->borderSize, $this->getBorderColor());
        });
    }

    protected function cacheKey()
    {
        $keys = [];
        $attributes = [
            'initials',
            'shape',
            'font',
            'fontSize',
            'width',
            'height',
            'background',
            'foreground',
            'borderSize',
            'borderColor',
        ];
        foreach ($attributes as $attr) {
            $keys[] = $this->$attr;
        }

        return md5(implode('-', $keys));
    }
}
