# laravolt/avatar

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d8a4b0d9-8603-406d-85c9-e0f5fa8c5650/mini.png)](https://insight.sensiolabs.com/projects/d8a4b0d9-8603-406d-85c9-e0f5fa8c5650)
[![Travis](https://img.shields.io/travis/laravolt/avatar.svg)](https://travis-ci.org/laravolt/avatar)
[![Coverage Status](https://coveralls.io/repos/github/laravolt/avatar/badge.svg?branch=master)](https://coveralls.io/github/laravolt/avatar?branch=master)

![Preview](https://user-images.githubusercontent.com/149716/29503524-840ccd0c-8662-11e7-92f9-9ec3ed8a24af.png)

Display unique avatar for any user based on their (initials) name.

## Preview
![Preview](https://cloud.githubusercontent.com/assets/149716/26735022/6dbd77e2-47ea-11e7-8a05-7772465309c5.png)

## Installation
This package originally built for Laravel, but can also be used in any PHP project.

[Read more about integration with PHP project here.](#integration-with-other-php-project)

### Laravel 5.2/5.3/5.4/5.5:
``` bash
$ composer require laravolt/avatar
```

### Laravel 5.1:
``` bash
composer require laravolt/avatar ~0.3
```

## Service Provider & Facade
**Note: only for Laravel 5.4 and below, because since Laravel 5.5 we use package auto-discovery.**

``` php
Laravolt\Avatar\ServiceProvider::class,

...

'Avatar'    => Laravolt\Avatar\Facade::class,
```
## Publish Config (Optional)
``` php
php artisan vendor:publish --provider="Laravolt\Avatar\ServiceProvider"
```
This will create config file located in `config/laravolt/avatar.php`.

## Lumen Service Provider

```php
$app->register(Laravolt\Avatar\LumenServiceProvider);
```

## Usage

### Output As Base64
```php
//this will output data-uri (base64 image data)
//something like data:image/png;base64,iVBORw0KGg....
Avatar::create('Joko Widodo')->toBase64();

//use in view
//this will display initials JW as an image
<img src="{{ Avatar::create('Joko Widodo')->toBase64() }}" />
```

### Save As File
```php
Avatar::create('Susilo Bambang Yudhoyono')->save('sample.png');
Avatar::create('Susilo Bambang Yudhoyono')->save('sample.jpg', 100); // quality = 100
```

### Output As SVG
```php
Avatar::create('Susilo Bambang Yudhoyono')->toSvg();
```

## Get underlying Intervention image object
```php
Avatar::create('Abdul Somad')->getImageObject();
```
The method will return an instance of [Intervention image object](http://image.intervention.io/), so you can use it for further purposes.

## Non-ASCII Character
By default, this package will try to output any initials letter as it is. If the name supplied contains any non-ASCII character (e.g. ā, Ě, ǽ) then the result will depend on which font used (see config). It the font supports characters supplied, it will successfully displayed, otherwise it will not.

Alternatively, we can convert all non-ascii to their closest ASCII counterparts. If no closest coutnerparts found, those characters are removed. Thanks to [Stringy](https://github.com/danielstjules/Stringy) for providing such useful functions. What we need is just change one line in `config/avatar.php`:

``` php
    'ascii'    => true,
```

## Configuration
``` php
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
    | configuration. By default PHP's "GD Library" implementation is used.
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

    // convert initial letter to uppercase
    'uppercase' => false,

    // Fonts used to render text.
    // If contains more than one fonts, randomly selected based on name supplied
    'fonts'    => ['path/to/OpenSans-Bold.ttf', 'path/to/rockwell.ttf'],

    // List of foreground colors to be used, randomly selected based on name supplied
    'foregrounds'   => [
        '#FFFFFF'
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
        'color' => 'foreground'
    ]
];

```

## Overriding config at runtime
We can overriding configuration at runtime by using following functions:

``` php
Avatar::create('Soekarno')->setDimension(100);//width = height = 100 pixel
Avatar::create('Soekarno')->setDimension(100, 200); // width = 100, height = 200
Avatar::create('Soekarno')->setBackground('#001122');
Avatar::create('Soekarno')->setForeground('#999999');
Avatar::create('Soekarno')->setFontSize(72);
Avatar::create('Soekarno')->setFont('/path/to/font.ttf');
Avatar::create('Soekarno')->setBorder(1, '#aabbcc'); // size = 1, color = #aabbcc
Avatar::create('Soekarno')->setShape('square');

// chaining
Avatar::create('Habibie')->setDimension(50)->setFontSize(18)->toBase64();

``` 

## Integration With Other PHP Project
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
