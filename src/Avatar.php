<?php
namespace Laravolt\Avatar;

use Illuminate\Support\Collection;
use Intervention\Image\Facades\Image;
use Stringy\Stringy;

class Avatar
{
    protected $chars;
    protected $colors;
    protected $fonts;

    /**
     * Avatar constructor.
     * @param int $chars
     * @param array $colors
     * @param $fonts
     */
    public function __construct($chars, array $colors, $fonts)
    {
        $this->chars = $chars;
        $this->colors = $colors;
        $this->fonts = $fonts;
    }


    public function data($name)
    {
        $initials = $this->getInitials($name);
        $bg = $this->getBackground($name);
        $img = Image::canvas(100, 100)->fill($bg);
        $img->text($initials, 50, 50, function ($font) use ($name) {
            $font->file(base_path('resources/laravolt/avatar/fonts/' . $this->getFont($name)));
            $font->size(38);
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
        return $this->fonts[$number % count($this->fonts)];
    }
}
