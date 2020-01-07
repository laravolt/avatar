<?php

namespace Laravolt\Avatar;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Repository;
use Intervention\Image\AbstractFont;
use Intervention\Image\AbstractShape;
use Intervention\Image\ImageManager;
use Laravolt\Avatar\Concerns\AttributeSetter;
use Laravolt\Avatar\Generator\DefaultGenerator;
use Laravolt\Avatar\Generator\GeneratorInterface;

class Avatar
{
    use AttributeSetter;

    protected $name;

    protected $chars;

    protected $shape;

    protected $width;

    protected $height;

    protected $availableBackgrounds = [];

    protected $availableForegrounds = [];

    protected $fonts = [];

    protected $fontSize;

    protected $fontFamily;

    protected $borderSize = 0;

    protected $borderColor;

    protected $ascii = false;

    protected $uppercase = false;

    /**
     * @var \Intervention\Image\Image
     */
    protected $image;

    protected $font = null;

    protected $background = '#CCCCCC';

    protected $foreground = '#FFFFFF';

    protected $initials = '';

    protected $cache;

    protected $driver;

    protected $initialGenerator;

    protected $defaultFont = __DIR__.'/../fonts/OpenSans-Bold.ttf';

    protected $themes = [];

    protected $theme;

    protected $defaultTheme = [];

    /**
     * Avatar constructor.
     *
     * @param  array  $config
     * @param  Repository  $cache
     */
    public function __construct(array $config = [], Repository $cache = null)
    {
        $this->cache = $cache ?? new ArrayStore();
        $this->driver = $config['driver'] ?? 'gd';
        $this->theme = $config['theme'] ?? null;
        $this->defaultTheme = $this->validateConfig($config);
        $this->applyTheme($this->defaultTheme);

        // Add any additional themes for further use
        $themes = $this->resolveTheme('*', $config['themes'] ?? []);
        foreach ($themes as $name => $config) {
            $this->addTheme($name, $config);
        }

        $this->initTheme();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->toBase64();
    }

    public function setGenerator(GeneratorInterface $generator)
    {
        $this->initialGenerator = $generator;
    }

    public function create($name)
    {
        $this->name = $name;

        $this->initTheme();

        return $this;
    }

    public function applyTheme(array $config)
    {
        $config = $this->validateConfig($config);
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
    }

    public function addTheme(string $name, array $config)
    {
        $this->themes[$name] = $this->validateConfig($config);

        return $this;
    }

    protected function setRandomTheme()
    {
        $themes = $this->resolveTheme($this->theme, $this->themes);
        if (!empty($themes)) {
            $this->applyTheme($this->getRandomElement($themes, []));
        }
    }

    protected function resolveTheme($theme, $config)
    {
        $config = collect($config);
        $themes = [];

        foreach ((array) $theme as $themeName) {
            if (!is_string($themeName)) {
                continue;
            }
            if ($themeName === '*') {
                foreach ($config as $name => $themeConfig) {
                    $themes[$name] = $themeConfig;
                }
            } else {
                $themes[$themeName] = $config->get($themeName, []);
            }
        }

        return $themes;
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

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$this->width.'" height="'.$this->height.'" viewBox="0 0 '.$this->width.' '.$this->height.'">';

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

        $svg .= '<text x="'.$center.'" y="'.$center;
        $svg .= '" font-size="'.$this->fontSize;

        if ($this->fontFamily) {
            $svg .= '" font-family="'.$this->fontFamily;
        }

        $svg .= '" fill="'.$this->foreground.'" alignment-baseline="middle" text-anchor="middle" dominant-baseline="central">';
        $svg .= $this->getInitial();
        $svg .= '</text>';

        $svg .= '</svg>';

        return $svg;
    }

    public function toGravatar(array $param = null)
    {
        // Hash generation taken from https://en.gravatar.com/site/implement/images/php/
        $hash = md5(strtolower(trim($this->name)));

        $attributes = [];
        if ($this->width) {
            $attributes['s'] = $this->width;
        }

        if (!empty($param)) {
            $attributes = $param + $attributes;
        }

        $url = sprintf('https://www.gravatar.com/avatar/%s', $hash);

        if (!empty($attributes)) {
            $url .= '?';
            ksort($attributes);
            foreach ($attributes as $key => $value) {
                $url .= "$key=$value&";
            }
            $url = substr($url, 0, -1);
        }

        return $url;
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

    protected function getRandomFont()
    {
        return $this->getRandomElement($this->fonts, $this->defaultFont);
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
        // Make it work for associative array
        $array = array_values($array);

        $name = $this->name;
        if (strlen($name) === 0) {
            $name = chr(rand(65, 90));
        }

        if (count($array) == 0) {
            return $default;
        }

        $number = ord($name[0]);
        $i = 1;
        $charLength = strlen($name);
        while ($i < $charLength) {
            $number += ord($name[$i]);
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

    protected function validateConfig($config)
    {
        $fallback = [
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

        return $config + $this->defaultTheme + $fallback;
    }

    protected function initTheme()
    {
        $this->setRandomTheme();
        $this->setForeground($this->getRandomForeground());
        $this->setBackground($this->getRandomBackground());
        $this->setFont($this->getRandomFont());
    }
}
