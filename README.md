# Avatar

![](https://dl.dropboxusercontent.com/u/21271348/laravolt/avatar/avatar-result.png)

Display unique, initial-based avatar (as base64) for Eloquent user. Can be used as default avatar if User doesn't have their own picture.

## Installation

Via Composer

``` bash
$ composer require laravolt/avatar
```
## Service Provider
``` php
Laravolt\Avatar\ServiceProvider::class,
```
## Facade (Alias)
``` php
'Avatar'    => Laravolt\Avatar\Facade::class,
```
## Publish Asset and Config
``` php
php artisan vendor:publish --provider="Laravolt\Avatar\ServiceProvider"
```
This will create config file located in `config/avatar.php` and a set of fonts located in `resources/laravolt/avatar/fonts`.

## Usage
```php
//this will outpu data-uri (base64 image data)
//something like data:image/png;base64,iVBORw0KGg....
Avatar::create('Joko Widodo')->toBase64();

//use in view
//this will display initials JW as an image
<img src="{{ Avatar::create('Joko Widodo')->toBase64() }}" />

```

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
    // Whether all characters supplied must be replaced with their closest ASCII counterparts
    'ascii'    => false,

    // image width, in pixel
    'width'    => 100,

    // image height, in pixel
    'height'   => 100,

    // number of characters used as initials
    'chars'    => 2,

    // font size
    'fontSize' => 48,

    // Fonts used to render text.
    // If contains more than one fonts, it will randomly choose which font used
    'fonts'    => ['OpenSans-Bold.ttf', 'rockwell.ttf'],

    // list of background colors to be used
    'colors'   => [
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

// chaining
Avatar::create('Habibie')->setDimension(50)->setFontSize(18)->toBase64();

``` 