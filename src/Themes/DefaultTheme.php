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

    public function __construct($string, $ascii = false, $charLimit = 2)
    {
        $this->ascii = $ascii;
        $this->charLimit = $charLimit;

        $this->string = Stringy::create($string)->collapseWhitespace();
        if ($this->ascii) {
            $this->string = $this->string->toAscii();
        }

        $this->makeInitials();
    }

    public function getText()
    {
        return $this->initials;
    }

    public function getBackground()
    {
        // TODO: Implement getBackground() method.
    }

    public function getForeground()
    {
        // TODO: Implement getForeground() method.
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
}
