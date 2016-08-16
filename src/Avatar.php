<?php

namespace Laravolt\Avatar;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Arr;
use Intervention\Image\AbstractFont;
use Intervention\Image\AbstractShape;
use Intervention\Image\ImageManager;

class Avatar
{
    protected $name;

    protected $chars;
    protected $shape;
    protected $width;
    protected $height;
    protected $availableBackgrounds;
    protected $availableForegrounds;
    protected $fonts;
    protected $fontSize;
    protected $borderSize = 0;
    protected $borderColor;
    protected $ascii = false;

    protected $image;
    protected $font = null;
    protected $background = '#cccccc';
    protected $foreground = '#ffffff';
    protected $initials = '';

    protected $cache;

    protected $initialGenerator;

    protected $fontFolder;
    protected $defaultFont = 5;

    /**
     * Avatar constructor.
     *
     * @param array            $config
     * @param CacheManager     $cache
     * @param InitialGenerator $initialGenerator
     */
    public function __construct(array $config, CacheManager $cache, InitialGenerator $initialGenerator)
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
        $this->initialGenerator = $initialGenerator->setUppercase(Arr::get($config, 'uppercase'));
    }

    /**
     * @return String
     */
    function __toString()
    {
        return (string) $this->toBase64();
    }


    public function create($name)
    {
        $this->name = $name;

        $this->initialGenerator->setName($name);
        $this->initialGenerator->setLength($this->chars);
        $this->initials = $this->initialGenerator->getInitial();

        $this->setForeground($this->getRandomForeground());
        $this->setBackground($this->getRandomBackground());

        return $this;
    }

    public function setFontFolder($folders)
    {
        $this->fontFolder = $folders;
    }

    public function setFont($font)
    {
        if (is_file($font)) {
            $this->font = $font;
        }

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

    public function getInitial()
    {
        return $this->initials;
    }

    public function getImageObject()
    {
        $this->buildAvatar();

        return $this->image;
    }

    protected function getRandomBackground()
    {
        return $this->getRandomElement($this->availableBackgrounds, $this->background);
    }

    protected function getRandomForeground()
    {
        return $this->getRandomElement($this->availableForegrounds, $this->foreground);
    }

    protected function setRandomFont()
    {
        $this->font = $this->defaultFont;

        $initials = $this->getInitial();

        if ($initials) {
            $number = ord($initials[0]);
            $font = $this->fonts[$number % count($this->fonts)];

            if (!is_array($this->fontFolder)) {
                throw new \Exception('Font folder not set');
            }

            foreach ($this->fontFolder as $folder) {
                $fontFile = $folder.$font;

                if (is_file($fontFile)) {
                    $this->font = $fontFile;
                    break;
                }
            }
        }

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

        $manager = new ImageManager(array('driver' => config('avatar.driver')));
        $this->image = $manager->canvas($this->width, $this->height);

        $this->createShape();

        $this->chooseFont();

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
            'name',
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

    protected function getRandomElement($array, $default)
    {
        if (strlen($this->name) == 0) {
            return $default;
        }

        $number = ord($this->name[0]);
        $i = 1;
        $charLength = strlen($this->name);
        while ($i < $charLength) {
            $number += ord($this->name[$i]);
            $i++;
        }

        return $array[$number % count($array)];
    }

    protected function chooseFont()
    {
        if (!$this->font) {
            $this->setRandomFont();
        }
    }
}
