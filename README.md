# laravolt/avatar

[![Total Downloads](http://poser.pugx.org/laravolt/avatar/downloads)](https://packagist.org/packages/laravolt/avatar)
[![Monthly Downloads](http://poser.pugx.org/laravolt/avatar/d/monthly)](https://packagist.org/packages/laravolt/avatar)
[![Run Tests](https://github.com/laravolt/avatar/workflows/run-tests/badge.svg)](https://github.com/laravolt/avatar/workflows/run-tests/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/laravolt/avatar/badge.svg?branch=master)](https://coveralls.io/github/laravolt/avatar?branch=master)

![Preview](https://user-images.githubusercontent.com/149716/29503524-840ccd0c-8662-11e7-92f9-9ec3ed8a24af.png)

Display unique avatar for any user based on their (initials) name.

## Preview
![Preview](https://cloud.githubusercontent.com/assets/149716/26735022/6dbd77e2-47ea-11e7-8a05-7772465309c5.png)
## :film_strip: Video Tutorial 
[<img src="https://img.youtube.com/vi/jD0wu88c5kw/0.jpg" width="250">](https://youtu.be/jD0wu88c5kw)

## Installation
This package originally built for Laravel, but can also be used in any PHP project.

[Read more about integration with PHP project here.](#integration-with-other-php-project)

### Laravel >= 5.2:
```bash
composer require laravolt/avatar
```

### Laravel 5.1:
```bash
composer require laravolt/avatar ~0.3
```

## Service Provider & Facade
**Note: only for Laravel 5.4 and below, because since Laravel 5.5 we use package auto-discovery.**

```php
Laravolt\Avatar\ServiceProvider::class,

...

'Avatar'    => Laravolt\Avatar\Facade::class,
```

## Publish Config (optional)
```php
php artisan vendor:publish --provider="Laravolt\Avatar\ServiceProvider"
```
This will create config file located in `config/laravolt/avatar.php`.

## Lumen Service Provider

```php
$app->register(Laravolt\Avatar\LumenServiceProvider);
```

## Usage

### Output as base64
```php
//this will output data-uri (base64 image data)
//something like data:image/png;base64,iVBORw0KGg....
Avatar::create('Joko Widodo')->toBase64();

//use in view
//this will display initials JW as an image
<img src="{{ Avatar::create('Joko Widodo')->toBase64() }}" />
```

### Save as file
```php
Avatar::create('Susilo Bambang Yudhoyono')->save('sample.png');
Avatar::create('Susilo Bambang Yudhoyono')->save('sample.jpg', 100); // quality = 100
```

### Output as Gravatar
```php
Avatar::create('uyab@example.net')->toGravatar();
// Output: http://gravatar.com/avatar/0dcae7d6d76f9a3b14588e9671c45879

Avatar::create('uyab@example.net')->toGravatar(['d' => 'identicon', 'r' => 'pg', 's' => 100]);
// Output: http://gravatar.com/avatar/0dcae7d6d76f9a3b14588e9671c45879?d=identicon&r=pg&s=100
```
Gravatar parameter reference: https://en.gravatar.com/site/implement/images/

### Output as SVG
```php
Avatar::create('Susilo Bambang Yudhoyono')->toSvg();
```

You may specify custom font-family for your SVG text.
```html
<head>
    <!--Prepare custom font family, using Google Fonts-->
    <link href="https://fonts.googleapis.com/css?family=Laravolt" rel="stylesheet">

    <!--OR-->

    <!--Setup your own style-->
    <style>
    @font-face {
        font-family: Laravolt;
        src: url({{ asset('fonts/laravolt.woff')) }});
    }
    </style>
</head>
```

```php
Avatar::create('Susilo Bambang Yudhoyono')->setFontFamily('Laravolt')->toSvg();
```

## Get underlying Intervention image object
```php
Avatar::create('Abdul Somad')->getImageObject();
```
The method will return an instance of [Intervention image object](http://image.intervention.io/), so you can use it for further purposes.

## Non-ASCII Character
By default, this package will try to output any initials letter as it is. If the name supplied contains any non-ASCII character (e.g. ā, Ě, ǽ) then the result will depend on which font used (see config). It the font supports characters supplied, it will successfully displayed, otherwise it will not.

Alternatively, we can convert all non-ascii to their closest ASCII counterparts. If no closest coutnerparts found, those characters are removed. Thanks to [Stringy](https://github.com/danielstjules/Stringy) for providing such useful functions. What we need is just change one line in `config/avatar.php`:

```php
    'ascii'    => true,
```

## Configuration
```php
<?php
/*
 * Set specific configuration variables here
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    | Avatar use Intervention Image library to process image.
    | Meanwhile, Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "Imagick" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */
    'driver'    => 'gd',

    // Initial generator class
    'generator' => \Laravolt\Avatar\Generator\DefaultGenerator::class,

    // Whether all characters supplied must be replaced with their closest ASCII counterparts
    'ascii'    => false,

    // Image shape: circle or square
    'shape' => 'circle',

    // Image width, in pixel
    'width'    => 100,

    // Image height, in pixel
    'height'   => 100,

    // Number of characters used as initials. If name consists of single word, the first N character will be used
    'chars'    => 2,

    // font size
    'fontSize' => 48,

    // convert initial letter in uppercase
    'uppercase' => false,

    // Right to Left (RTL)
    'rtl' => false,

    // Fonts used to render text.
    // If contains more than one fonts, randomly selected based on name supplied
    'fonts'    => [__DIR__.'/../fonts/OpenSans-Bold.ttf', __DIR__.'/../fonts/rockwell.ttf'],

    // List of foreground colors to be used, randomly selected based on name supplied
    'foregrounds'   => [
        '#FFFFFF',
    ],

    // List of background colors to be used, randomly selected based on name supplied
    'backgrounds'   => [
        '#f44336',
        '#E91E63',
        '#9C27B0',
        '#673AB7',
        '#3F51B5',
        '#2196F3',
        '#03A9F4',
        '#00BCD4',
        '#009688',
        '#4CAF50',
        '#8BC34A',
        '#CDDC39',
        '#FFC107',
        '#FF9800',
        '#FF5722',
    ],

    'border'    => [
        'size'  => 1,

        // border color, available value are:
        // 'foreground' (same as foreground color)
        // 'background' (same as background color)
        // or any valid hex ('#aabbcc')
        'color' => 'background',

        // border radius, only works for SVG
        'radius' => 0,
    ],

    // List of theme name to be used when rendering avatar
    // Possible values are:
    // 1. Theme name as string: 'colorful'
    // 2. Or array of string name: ['grayscale-light', 'grayscale-dark']
    // 3. Or wildcard "*" to use all defined themes
    'theme' => ['*'],

    // Predefined themes
    // Available theme attributes are:
    // shape, chars, backgrounds, foregrounds, fonts, fontSize, width, height, ascii, uppercase, and border.
    'themes' => [
        'grayscale-light' => [
            'backgrounds' => ['#edf2f7', '#e2e8f0', '#cbd5e0'],
            'foregrounds' => ['#a0aec0'],
        ],
        'grayscale-dark' => [
            'backgrounds' => ['#2d3748', '#4a5568', '#718096'],
            'foregrounds' => ['#e2e8f0'],
        ],
        'colorful' => [
            'backgrounds' => [
                '#f44336',
                '#E91E63',
                '#9C27B0',
                '#673AB7',
                '#3F51B5',
                '#2196F3',
                '#03A9F4',
                '#00BCD4',
                '#009688',
                '#4CAF50',
                '#8BC34A',
                '#CDDC39',
                '#FFC107',
                '#FF9800',
                '#FF5722',
            ],
            'foregrounds' => ['#FFFFFF'],
        ],
    ]
];
```

## Overriding config at runtime
We can overriding configuration at runtime by using following functions:

```php
Avatar::create('Soekarno')->setDimension(100);//width = height = 100 pixel
Avatar::create('Soekarno')->setDimension(100, 200); // width = 100, height = 200
Avatar::create('Soekarno')->setBackground('#001122');
Avatar::create('Soekarno')->setForeground('#999999');
Avatar::create('Soekarno')->setFontSize(72);
Avatar::create('Soekarno')->setFont('/path/to/font.ttf');
Avatar::create('Soekarno')->setBorder(1, '#aabbcc'); // size = 1, color = #aabbcc
Avatar::create('Soekarno')->setBorder(1, '#aabbcc', 10); // size = 1, color = #aabbcc, border radius = 10 (only for SVG)
Avatar::create('Soekarno')->setShape('square');

// Available since 3.0.0
Avatar::create('Soekarno')->setTheme('colorful'); // set exact theme
Avatar::create('Soekarno')->setTheme(['grayscale-light', 'grayscale-dark']); // theme will be randomized from these two options

// chaining
Avatar::create('Habibie')->setDimension(50)->setFontSize(18)->toBase64();
```

## Integration with other PHP project
```php
// include composer autoload
require 'vendor/autoload.php';

// import the Avatar class
use Laravolt\Avatar\Avatar;

// create your first avatar
$avatar = new Avatar($config);
$avatar->create('John Doe')->toBase64();
$avatar->create('John Doe')->save('path/to/file.png', $quality = 90);
```
`$config` is just an ordinary array with same format as explained above (See [Configuration](#configuration)).

## Support Us

### Buy Me A Coffee
[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/yellow_img.png)](https://www.buymeacoffee.com/uyab)

### Donate Via PayPal
[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://paypal.me/bayuhendra)

### Traktir Saya
<a href="https://trakteer.id/bayuhendra/tip" target="_blank"><img id="wse-buttons-preview" src="https://cdn.trakteer.id/images/embed/trbtn-red-5.png" height="40" style="border:0px;height:40px;" alt="Trakteer Saya"></a>
