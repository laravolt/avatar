<?php
namespace Laravolt\Avatar\Themes;

use Illuminate\Support\Collection;
use Laravolt\Avatar\Contracts\Theme;
use Stringy\Stringy;

class DefaultTheme implements Theme
{
    protected $string;
    protected $initials = '';
    protected $charLimit = 2;
    protected $ascii = false;
    protected $backgrounds = ['#999999'];
    protected $foregrounds = ['#FFFFFF'];
    protected $fonts = null;

    public function __construct($string = '', $ascii = false, $charLimit = 2, $backgrounds = [], $foregrounds = [], $fonts = [])
    {
        $this->ascii = $ascii;
        $this->charLimit = $charLimit;
        $this->fonts = $fonts;

        $this->setText($string);
        $this->setBackgrounds($backgrounds);
        $this->setForegrounds($foregrounds);
    }

    public function setText($text)
    {
        if (is_array($text)) {
            throw new \InvalidArgumentException(
                'Passed value cannot be an array'
            );
        } elseif (is_object($text) && !method_exists($text, '__toString')) {
            throw new \InvalidArgumentException(
                'Passed object must have a __toString method'
            );
        }

        $this->string = Stringy::create($text)->collapseWhitespace();
        if ($this->ascii) {
            $this->string = $this->string->toAscii();
        }

        $this->makeInitials();
    }

    public function setBackgrounds(array $backgrounds)
    {
        if (!empty($backgrounds)) {
            $this->backgrounds = $backgrounds;
        }
    }

    public function setForegrounds(array $foregrounds)
    {
        if (!empty($foregrounds)) {
            $this->foregrounds = $foregrounds;
        }
    }

    public function getText()
    {
        return $this->initials;
    }

    public function getBackground()
    {
        return $this->getColorByText($this->getText(), $this->backgrounds);
    }

    public function getForeground()
    {
        return $this->getColorByText($this->getText(), $this->foregrounds);
    }

    public function getFont()
    {
        $initials = $this->getText();

        if ($initials) {
            $number = ord($initials[0]);
            $key = $number % count($this->fonts);
            $font = array_get($this->fonts, $key);
            $fontFile = base_path('resources/laravolt/avatar/fonts/' . $font);
            if ($font && is_file($fontFile)) {
                return $fontFile;
            }
        }

        return 5;
    }

    protected function makeInitials()
    {
        $words = new Collection(explode(' ', $this->string));

        // if name contains single word, use first N character
        if ($words->count() === 1) {

            $length = min($this->charLimit, $this->string->length());

            $initials = $this->string->substr(0, $length);
        } else {
            // otherwise, use initial char from each word
            $chars = new Collection();

            $words->each(function ($word) use ($chars) {
                $chars->push(Stringy::create($word)->substr(0, 1));
            });

            $initials = $chars->slice(0, $this->charLimit)->implode('');
        }

        $this->initials = $initials;
    }

    protected function getColorByText($text, $colors)
    {
        $charLength = strlen($text);
        if ($charLength == 0) {
            return $colors[0];
        }

        $number = ord($text[0]);
        $i = 1;
        while ($i < $charLength) {
            $number += ord($text[$i]);
            $i++;
        }

        return $colors[$number % count($colors)];

    }
}
