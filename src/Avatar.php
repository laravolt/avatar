<?php

namespace Laravolt\Avatar;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Intervention\Image\AbstractFont;
use Intervention\Image\AbstractShape;
use Intervention\Image\Facades\Image;
use Stringy\Stringy;

class Avatar
{
    protected $shape;
    protected $name;
    protected $chars;
    protected $availableBackgrounds;
    protected $availableForegrounds;
    protected $fonts;
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
    protected $ascii = false;

    protected $cache;

    /**
     * Avatar constructor.
     *
     * @param array $config
     * @param CacheManager $cache
     */
    public function __construct(array $config, CacheManager $cache)
    {
        $this->shape = Arr::get($config, 'shape', 'circle');
        $this->chars = Arr::get($config, 'chars', 2);
        $this->availableBackgrounds = Arr::get($config, 'backgrounds', [$this->background]);
        $this->availableForegrounds = Arr::get($config, 'foregrounds', [$this->foreground]);
        $this->fonts = Arr::get($config, 'fonts', [1]);
        $this->fontSize = Arr::get($config, 'fontSize', 32);
        $this->width = Arr::get($config, 'width', 100);
        $this->height = Arr::get($config, 'height', 100);
        $this->ascii = Arr::get($config, 'ascii', false);
        $this->borderSize = Arr::get($config, 'border.size');
        $this->borderColor = Arr::get($config, 'border.color');

        $this->cache = $cache;
    }

    public function create($name)
    {
        if (is_array($name)) {
            throw new \InvalidArgumentException(
                'Passed value cannot be an array'
            );
        } elseif (is_object($name) && !method_exists($name, '__toString')) {
            throw new \InvalidArgumentException(
                'Passed object must have a __toString method'
            );
        }

        $this->name = Stringy::create($name)->collapseWhitespace();
        if ($this->ascii) {
            $this->name = $this->name->toAscii();
        }

        $this->initials = $this->getInitials();
        $this->setFont();
        $this->setForeground($this->getRandomForeground());
        $this->setBackground($this->getRandomBackground());

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

    protected function getInitials()
    {
        $words = new Collection(explode(' ', $this->name));

        // if name contains single word, use first N character
        if ($words->count() === 1) {
            if ($this->name->length() >= $this->chars) {
                return $this->name->substr(0, $this->chars);
            }

            return (string)$words->first();
        }

        // otherwise, use initial char from each word
        $initials = new Collection();
        $words->each(function ($word) use ($initials) {
            $initials->push(Stringy::create($word)->substr(0, 1));
        });

        return $initials->slice(0, $this->chars)->implode('');
    }

    protected function getRandomBackground()
    {
        if (strlen($this->initials) == 0) {
            return $this->background;
        }

        $number = ord($this->initials[0]);
        $i = 1;
        $charLength = strlen($this->initials);
        while ($i < $charLength) {
            $number += ord($this->initials[$i]);
            $i++;
        }

        return $this->availableBackgrounds[$number % count($this->availableBackgrounds)];
    }

    protected function getRandomForeground()
    {
        if (strlen($this->initials) == 0) {
            return $this->foreground;
        }

        $number = ord($this->initials[0]);
        $i = 1;
        $charLength = strlen($this->initials);
        while ($i < $charLength) {
            $number += ord($this->initials[$i]);
            $i++;
        }

        return $this->availableForegrounds[$number % count($this->availableForegrounds)];
    }

    protected function setFont()
    {
        $initials = $this->getInitials();

        if ($initials) {
            $number = ord($initials[0]);
            $font = $this->fonts[$number % count($this->fonts)];

            // list of folder to scan where font located, order by priority
            $fontFolder = [
                // no folder at all, allow developer to supply full path to file in their configuration
                "",

                // find file located in published asset folder
                base_path('resources/laravolt/avatar/fonts/'),

                // find font included by default in package
                __DIR__ . '/../resources/assets/fonts/',
            ];

            foreach ($fontFolder as $folder) {
                $fontFile = $folder . $font;

                if (is_file($fontFile)) {
                    $this->font = $fontFile;

                    return true;
                }
            }
        }

        $this->font = 5;
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

        $this->image->text($this->initials, $x, $y, function (AbstractFont $font) {
            $font->file($this->font);
            $font->size($this->fontSize);
            $font->color($this->foreground);
            $font->align('center');
            $font->valign('middle');
        });
    }

    protected function createShape()
    {
        $method = 'create' . ucfirst($this->shape) . 'Shape';
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

        $this->image->circle($circleDiameter, $x, $y, function (AbstractShape $draw) {
            $draw->background($this->background);
            $draw->border($this->borderSize, $this->getBorderColor());
        });
    }

    protected function createSquareShape()
    {
        $x = $y = $this->borderSize;
        $width = $this->width - ($this->borderSize * 2);
        $height = $this->height - ($this->borderSize * 2);
        $this->image->rectangle($x, $y, $width, $height, function (AbstractShape $draw) {
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
            'chars',
            'font',
            'fontSize',
            'width',
            'height',
            'borderSize',
            'borderColor',
        ];
        foreach ($attributes as $attr) {
            $keys[] = $this->$attr;
        }

        return md5(implode('-', $keys));
    }
}
