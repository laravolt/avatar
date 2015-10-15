<?php
namespace Laravolt\Avatar;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Intervention\Image\Facades\Image;
use Stringy\Stringy;

class Avatar
{
    protected $chars;
    protected $colors;
    protected $fonts;
    protected $fontSize;
    protected $width;
    protected $height;

    /**
     * Avatar constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->chars = Arr::get($config, 'chars', 2);
        $this->colors = Arr::get($config, 'colors', ['#999999']);
        $this->fonts = Arr::get($config, 'fonts', [1]);
        $this->fontSize = Arr::get($config, 'fontSize', 32);
        $this->width = Arr::get($config, 'width', 100);
        $this->height = Arr::get($config, 'height', 100);
    }


    public function data($name)
    {
        $initials = $this->getInitials($name);
        $bg = $this->getBackground($name);
        $img = Image::canvas($this->width, $this->height)->fill($bg);

        $x = $this->width / 2;
        $y = $this->height / 2;

        $img->text($initials, $x, $y, function ($font) use ($name) {
            $font->file($this->getFont($name));
            $font->size($this->fontSize);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        return $img->encode('data-url');
    }

    protected function getInitials($name)
    {
        $name = Stringy::create($name)->collapseWhitespace();
        $words = new Collection(explode(' ', $name));

        // if name contains single word, use first N character
        if ($words->count() === 1) {
            return Stringy::create($words->first())->substr(0, $this->chars);
        }

        // otherwise, use initial char from each word
        $initials = new Collection();
        $words->each(function ($word) use ($initials) {
            $initials->push(Stringy::create($word)->substr(0, 1));
        });

        return $initials->slice(0, $this->chars)->implode('');
    }

    protected function getBackground($name)
    {
        $number = ord($this->getInitials($name)[0]);

        return $this->colors[$number % count($this->colors)];
    }

    protected function getFont($name)
    {
        $number = ord($this->getInitials($name)[0]);
        $font = $this->fonts[$number % count($this->fonts)];
        $fontFile = base_path('resources/laravolt/avatar/fonts/' . $font);
        if (is_file($fontFile)) {
            return $fontFile;
        }

        return 5;
    }
}
