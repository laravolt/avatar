# Avatar

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d8a4b0d9-8603-406d-85c9-e0f5fa8c5650/big.png)](https://insight.sensiolabs.com/projects/d8a4b0d9-8603-406d-85c9-e0f5fa8c5650)

Display unique avatar for any user based on their name. Can be used as default avatar when user has not uploaded the avatar image itself.

![](https://dl.dropboxusercontent.com/u/21271348/laravolt/avatar/avatar-result.png)

## Installation

### Laravel 5.2:

``` bash
$ composer require laravolt/avatar
```

### Laravel 5.1:
``` bash
composer require laravolt/avatar ~0.3
```

## Service Provider
``` php
Intervention\Image\ImageServiceProvider::class,
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

//save to file
Avatar::create('Susilo Bambang Yudhoyono')->save('sample.png');
Avatar::create('Susilo Bambang Yudhoyono')->save('sample.jpg', 100); // quality = 100

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

    // Fonts used to render text.
    // If contains more than one fonts, randomly selected based on name supplied
    // You can provide absolute path, path relative to folder resources/laravolt/avatar/fonts/, or mixed.    
    'fonts'    => ['OpenSans-Bold.ttf', 'rockwell.ttf'],

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
Avatar::create('Soekarno')->setBorder(1, '#aabbcc'); // size = 1, color = #aabbcc
Avatar::create('Soekarno')->setShape('square');

// chaining
Avatar::create('Habibie')->setDimension(50)->setFontSize(18)->toBase64();

``` 
