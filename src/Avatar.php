<?php

namespace Laravolt\Avatar;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Repository;
use Intervention\Image\AbstractFont;
use Intervention\Image\AbstractShape;
use Intervention\Image\ImageManager;
use Laravolt\Avatar\Generator\DefaultGenerator;
use Laravolt\Avatar\Generator\GeneratorInterface;

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
    protected $uppercase = false;

    /**
     * @var \Intervention\Image\Image
     */
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
     * @param array      $config
     * @param Repository $cache
     */
    public function __construct(array $config = [], Repository $cache = null)
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
        $this->uppercase = $config['uppercase'];
        $this->borderSize = $config['border']['size'];
        $this->borderColor = $config['border']['color'];

        if (\is_null($cache)) {
            $cache = new ArrayStore();
        }

        $this->cache = $cache;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->toBase64();
    }

    public function setGenerator(GeneratorInterface $generator)
    {
        $this->initialGenerator = $generator;
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
        if ($base64 = $this->cache->get($key)) {
            return $base64;
        }

        $this->buildAvatar();

        $base64 = $this->image->encode('data-url');

        $this->cache->put($key, $base64, 0);

        return $base64;
    }

    public function save($path, $quality = 90)
    {
        $this->buildAvatar();

        return $this->image->save($path, $quality);
    }

    public function toSvg()
    {
        $this->buildInitial();

        $x = $y = $this->borderSize / 2;
        $width = $height = $this->width - $this->borderSize;
        $radius = ($this->width - $this->borderSize) / 2;
        $center = $this->width / 2;

        $svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" width="'.$this->width.'" height="'.$this->height.'">';

        if ($this->shape == 'square') {
            $svg .= '<rect x="'.$x
                .'" y="'.$y
                .'" width="'.$width.'" height="'.$height
                .'" stroke="'.$this->borderColor
                .'" stroke-width="'.$this->borderSize
                .'" fill="'.$this->background.'" />';
        } elseif ($this->shape == 'circle') {
            $svg .= '<circle cx="'.$center
                .'" cy="'.$center
                .'" r="'.$radius
                .'" stroke="'.$this->borderColor
                .'" stroke-width="'.$this->borderSize
                .'" fill="'.$this->background.'" />';
        }

        $svg .= '<text x="'.$center.'" y="'.$center
            .'" font-size="'.$this->fontSize
            .'" fill="'.$this->foreground.'" alignment-baseline="middle" text-anchor="middle">'
            .$this->getInitial()
            .'</text>';

        $svg .= '</svg>';

        return $svg;
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

    public function buildAvatar()
    {
        $this->buildInitial();

        $x = $this->width / 2;
        $y = $this->height / 2;

        $manager = new ImageManager(['driver' => $this->driver]);
        $this->image = $manager->canvas($this->width, $this->height);

        $this->createShape();

        $this->setRandomFont();

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

        return $this;
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
        $edge = (ceil($this->borderSize / 2));
        $x = $y = $edge;
        $width = $this->width - $edge;
        $height = $this->height - $edge;

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

    protected function buildInitial()
    {
        // fallback to default
        if (!$this->initialGenerator) {
            $this->initialGenerator = new DefaultGenerator();
        }

        $this->initials = $this->initialGenerator->make($this->name, $this->chars, $this->uppercase, $this->ascii);
    }
}
