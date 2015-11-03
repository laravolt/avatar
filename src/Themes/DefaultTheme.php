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

    public function __construct($string, $ascii = false, $charLimit = 2, $backgrounds = [], $foregrounds = [])
    {
        $this->ascii = $ascii;
        $this->charLimit = $charLimit;

        $this->setBackgrounds($backgrounds);
        $this->setForegrounds($foregrounds);

        $this->string = Stringy::create($string)->collapseWhitespace();
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
