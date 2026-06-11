<?php

namespace Laravolt\Avatar;

/**
 * @method static \Laravolt\Avatar\Avatar create(string $name)
 * @method static \Laravolt\Avatar\Avatar setBackground(string $color)
 * @method static \Laravolt\Avatar\Avatar setForeground(string $color)
 * @method static \Laravolt\Avatar\Avatar setDimension(int $width, ?int $height = null)
 * @method static \Laravolt\Avatar\Avatar setFontSize(float $size)
 * @method static \Laravolt\Avatar\Avatar setFont(string $font)
 * @method static \Laravolt\Avatar\Avatar setShape(string $shape)
 * @method static \Laravolt\Avatar\Avatar setChars(int $chars)
 * @method static \Laravolt\Avatar\Avatar setBorder(int $size, string $color, int $radius = 0)
 * @method static \Laravolt\Avatar\Avatar setBorderRadius(int $radius)
 * @method static \Laravolt\Avatar\Avatar setTheme(array|string $theme)
 * @method static \Laravolt\Avatar\Avatar setResponsive(bool $responsive)
 * @method static \Laravolt\Avatar\Avatar buildAvatar()
 * @method static string toBase64()
 * @method static string toSvg()
 * @method static string toGravatar()
 * @method static string getInitial()
 *
 * @see \Laravolt\Avatar\Avatar
 */
class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return 'avatar';
    }
}
