<?php

namespace Laravolt\Avatar;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Repository;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use Laravolt\Avatar\Concerns\AttributeGetter;
use Laravolt\Avatar\Concerns\AttributeSetter;
use Laravolt\Avatar\Generator\DefaultGenerator;
use Laravolt\Avatar\Generator\GeneratorInterface;

class Avatar
{
    use AttributeGetter;
    use AttributeSetter;

    protected ?string $name = "";

    protected int $chars;

    protected string $shape;

    protected int $width;

    protected int $height;

    protected bool $responsive = false;

    protected array $availableBackgrounds = [];

    protected array $availableForegrounds = [];

    protected array $fonts = [];

    protected float $fontSize;

    protected ?string $fontFamily = null;

    protected int $borderSize = 0;

    protected string $borderColor;

    protected int $borderRadius = 0;

    protected bool $ascii = false;

    protected bool $uppercase = false;

    protected bool $rtl = false;

    protected \Intervention\Image\Image $image;

    protected ?string $font;

    protected string $background = '#CCCCCC';

    protected string $foreground = '#FFFFFF';

    protected string $initials = '';

    protected Repository|ArrayStore $cache;

    protected mixed $driver;

    protected GeneratorInterface $initialGenerator;

    protected string $defaultFont = __DIR__.'/../fonts/OpenSans-Bold.ttf';

    protected array $themes = [];

    protected string|array|null $theme;

    protected array $defaultTheme = [];

    /**
     * Avatar constructor.
     *
     * @param array $config
     * @param Repository $cache
     */
    public function __construct(array $config = [], Repository $cache = null)
    {
        $this->cache = $cache ?? new ArrayStore();
        $this->driver = $config['driver'] ?? 'gd';
        $this->theme = $config['theme'] ?? null;
        $this->defaultTheme = $this->validateConfig($config);
        $this->applyTheme($this->defaultTheme);
        $this->initialGenerator = new DefaultGenerator();

        // Add any additional themes for further use
        $themes = $this->resolveTheme('*', $config['themes'] ?? []);
        foreach ($themes as $name => $conf) {
            $this->addTheme($name, $conf);
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

    public function setGenerator(GeneratorInterface $generator): void
    {
        $this->initialGenerator = $generator;
    }

    public function create(string $name): static
    {
        $this->name = $name;

        $this->initTheme();

        return $this;
    }

    public function applyTheme(array $config): void
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
        $this->responsive = $config['responsive'];
        $this->ascii = $config['ascii'];
        $this->uppercase = $config['uppercase'];
        $this->rtl = $config['rtl'];
        $this->borderSize = $config['border']['size'];
        $this->borderColor = $config['border']['color'];
        $this->borderRadius = $config['border']['radius'];
    }

    public function addTheme(string $name, array $config): static
    {
        $this->themes[$name] = $this->validateConfig($config);

        return $this;
    }

    protected function setRandomTheme(): void
    {
        $themes = $this->resolveTheme($this->theme, $this->themes);
        if (!empty($themes)) {
            $this->applyTheme($this->getRandomElement($themes, []));
        }
    }

    protected function resolveTheme(array|string|null $theme, array $cfg): array
    {
        $config = collect($cfg);
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

    public function toBase64(): string
    {
        $key = $this->cacheKey();
        if ($base64 = $this->cache->get($key)) {
            return $base64;
        }

        $this->buildAvatar();

        $base64 = $this->image->toPng()->toDataUri();

        $this->cache->forever($key, $base64);

        return $base64;
    }

    public function save(?string $path, int $quality = 90): \Intervention\Image\Interfaces\ImageInterface
    {
        $this->buildAvatar();

        return $this->image->save($path, $quality);
    }

    public function toSvg(): string
    {
        $this->buildInitial();

        $x = $y = $this->borderSize / 2;
        $width = $height = $this->width - $this->borderSize;
        $radius = ($this->width - $this->borderSize) / 2;
        $center = $this->width / 2;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg"';
        if (! $this->responsive) {
            $svg .= ' width="'.$this->width.'" height="'.$this->height.'"';
        }
        $svg .= ' viewBox="0 0 '.$this->width.' '.$this->height.'">';

        if ($this->shape === 'square') {
            $svg .= '<rect x="'.$x
                .'" y="'.$y
                .'" width="'.$width.'" height="'.$height
                .'" stroke="'.$this->getBorderColor()
                .'" stroke-width="'.$this->borderSize
                .'" rx="'.$this->borderRadius
                .'" fill="'.$this->background.'" />';
        } elseif ($this->shape === 'circle') {
            $svg .= '<circle cx="'.$center
                .'" cy="'.$center
                .'" r="'.$radius
                .'" stroke="'.$this->getBorderColor()
                .'" stroke-width="'.$this->borderSize
                .'" fill="'.$this->background.'" />';
        }

        $svg .= '<text font-size="'.$this->fontSize;

        if ($this->fontFamily) {
            $svg .= '" font-family="'.$this->fontFamily;
        }

        $svg .= '" fill="'.$this->foreground.'" x="50%" y="50%" dy=".1em" style="line-height:1" alignment-baseline="middle" text-anchor="middle" dominant-baseline="central">';
        $svg .= $this->getInitial();
        $svg .= '</text>';

        $svg .= '</svg>';

        return $svg;
    }

    public function toGravatar(array $param = null): string
    {
        // Hash generation taken from https://docs.gravatar.com/api/avatars/php/
        $hash = hash('sha256', strtolower(trim($this->name)));

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

    public function getInitial(): string
    {
        return $this->initials;
    }

    public function getImageObject(): \Intervention\Image\Image
    {
        $this->buildAvatar();

        return $this->image;
    }

    protected function getRandomBackground(): string
    {
        return $this->getRandomElement($this->availableBackgrounds, $this->background);
    }

    protected function getRandomForeground(): string
    {
        return $this->getRandomElement($this->availableForegrounds, $this->foreground);
    }

    protected function getRandomFont(): string
    {
        return $this->getRandomElement($this->fonts, $this->defaultFont);
    }

    protected function getBorderColor(): string
    {
        if ($this->borderColor === 'foreground') {
            return $this->foreground;
        }
        if ($this->borderColor === 'background') {
            return $this->background;
        }

        return $this->borderColor;
    }

    public function buildAvatar(): static
    {
        $this->buildInitial();

        $x = $this->width / 2;
        $y = $this->height / 2;

        $driver = $this->driver === 'gd' ? new Driver() : new ImagickDriver();
        $manager = new ImageManager($driver);
        $this->image = $manager->create($this->width, $this->height);

        $this->createShape();

        if (empty($this->initials)) {
            return $this;
        }

        $this->image->text(
            $this->initials,
            (int) $x,
            (int) $y,
            function (FontFactory $font) {
                $font->file($this->font);
                $font->size($this->fontSize);
                $font->color($this->foreground);
                $font->align('center');
                $font->valign('middle');
            }
        );

        return $this;
    }

    protected function createShape(): void
    {
        $method = 'create'.ucfirst($this->shape).'Shape';
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            throw new \InvalidArgumentException("Shape [$this->shape] currently not supported.");
        }
    }

    protected function createCircleShape(): void
    {
        $circleDiameter = (int) ($this->width - $this->borderSize);
        $x = (int) ($this->width / 2);
        $y = (int) ($this->height / 2);

        $this->image->drawCircle(
            $x,
            $y,
            function (CircleFactory $circle) use ($circleDiameter) {
                $circle->diameter($circleDiameter);
                $circle->border($this->getBorderColor(), $this->borderSize);
                $circle->background($this->background);
            }
        );
    }

    protected function createSquareShape(): void
    {
        $edge = (ceil($this->borderSize / 2));
        $x = $y = $edge;
        $width = $this->width - $edge;
        $height = $this->height - $edge;

        $this->image->drawRectangle(
            $x,
            $y,
            function (RectangleFactory $draw) use ($width, $height) {
                $draw->size($width, $height);
                $draw->background($this->background);
                $draw->border($this->getBorderColor(), $this->borderSize);
            }
        );
    }

    protected function cacheKey(): string
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

    /**
     * @throws \Random\RandomException
     */
    protected function getRandomElement(array $array, mixed $default): mixed
    {
        // Make it work for associative array
        $array = array_values($array);

        $name = $this->name;
        if ($name === null || $name === '') {
            $name = chr(random_int(65, 90));
        }

        if (empty($array)) {
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

    protected function buildInitial(): void
    {
        $this->initials = $this->initialGenerator->make($this->name, $this->chars, $this->uppercase, $this->ascii, $this->rtl);
    }

    protected function validateConfig(array $config): array
    {
        $fallback = [
            'shape' => 'circle',
            'chars' => 2,
            'backgrounds' => [$this->background],
            'foregrounds' => [$this->foreground],
            'fonts' => [$this->defaultFont],
            'fontSize' => 48,
            'width' => 100,
            'height' => 100,
            'responsive' => false,
            'ascii' => false,
            'uppercase' => false,
            'rtl' => false,
            'border' => [
                'size' => 1,
                'color' => 'foreground',
                'radius' => 0,
            ],
        ];

        // Handle nested config
        $config['border'] = ($config['border'] ?? []) + ($this->defaultTheme['border'] ?? []) + $fallback['border'];

        return $config + $this->defaultTheme + $fallback;
    }

    protected function initTheme(): void
    {
        $this->setRandomTheme();
        $this->setForeground($this->getRandomForeground());
        $this->setBackground($this->getRandomBackground());
        $this->setFont($this->getRandomFont());
    }
}
