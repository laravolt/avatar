<?php

namespace Laravolt\Avatar;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Repository;
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
    protected $driver;

    protected $initialGenerator;

    protected $defaultFont = __DIR__.'/../fonts/OpenSans-Bold.ttf';

    /**
     * Avatar constructor.
     *
     * @param array            $config
     * @param Repository       $cache
     * @param InitialGenerator $initialGenerator
     */
    public function __construct(array $config = [], Repository $cache = null, InitialGenerator $initialGenerator = null)
    {
        $default = [
            'driver'      => 'gd',
            'shape'       => 'circle',
            'chars'       => 2,
            'backgrounds' => [$this->background],
            'foregrounds' => [$this->foreground],
            'fonts'       => [$this->defaultFont],
            'fontSize'    => 48,
            'width'       => 100,
            'height'      => 100,
            'ascii'       => false,
            'uppercase'   => false,
            'border'      => [
                'size'  => 1,
                'color' => 'foreground',
            ],
        ];

        $config += $default;

        $this->driver = $config['driver'];
        $this->shape = $config['shape'];
        $this->chars = $config['chars'];
        $this->availableBackgrounds = $config['backgrounds'];
        $this->availableForegrounds = $config['foregrounds'];
        $this->fonts = $config['fonts'];
        $this->font = $this->defaultFont;
        $this->fontSize = $config['fontSize'];
        $this->width = $config['width'];
        $this->height = $config['height'];
        $this->ascii = $config['ascii'];
        $this->borderSize = $config['border']['size'];
        $this->borderColor = $config['border']['color'];

        if (\is_null($cache)) {
            $cache = new ArrayStore();
        }

        if (\is_null($initialGenerator)) {
            $initialGenerator = new InitialGenerator();
        }

        $this->cache = $cache;
        $this->initialGenerator = $initialGenerator;

        $this->initialGenerator->setUppercase($config['uppercase']);
        $this->initialGenerator->setAscii($config['ascii']);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->toBase64();
    }

    public function create($name)
    {
        $this->name = $name;

        $this->setForeground($this->getRandomForeground());
        $this->setBackground($this->getRandomBackground());

        return $this;
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
        $key = $this->cacheKey();
        if ($avatar = $this->cache->get($key)) {
            return $avatar;
        }

        $this->buildAvatar();

        return $this->image->encode('data-url');
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
        $randomFont = $this->getRandomElement($this->fonts, $this->defaultFont);

        $this->setFont($randomFont);
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
        $this->buildInitial();

        $x = $this->width / 2;
        $y = $this->height / 2;

        $manager = new ImageManager(['driver' => $this->driver]);
        $this->image = $manager->canvas($this->width, $this->height);

        $this->createShape();

        $this->chooseFont();

        $this->image->text(
            $this->initials,
            $x,
            $y,
            function (AbstractFont $font) {
                $font->file($this->font);
                $font->size($this->fontSize);
                $font->color($this->foreground);
                $font->align('center');
                $font->valign('middle');
            }
        );
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

        $this->image->circle(
            $circleDiameter,
            $x,
            $y,
            function (AbstractShape $draw) {
                $draw->background($this->background);
                $draw->border($this->borderSize, $this->getBorderColor());
            }
        );
    }

    protected function createSquareShape()
    {
        $x = $y = $this->borderSize;
        $width = $this->width - ($this->borderSize * 2);
        $height = $this->height - ($this->borderSize * 2);
        $this->image->rectangle(
            $x,
            $y,
            $width,
            $height,
            function (AbstractShape $draw) {
                $draw->background($this->background);
                $draw->border($this->borderSize, $this->getBorderColor());
            }
        );
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
        if (strlen($this->name) == 0 || count($array) == 0) {
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

    protected function buildInitial()
    {
        $this->initialGenerator->setName($this->name);
        $this->initialGenerator->setLength($this->chars);
        $this->initials = $this->initialGenerator->make();
    }
}
