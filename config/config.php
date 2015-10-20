<?php
/*
 * Set specific configuration variables here
 */
return [
    // Whether all characters supplied must be replaced with their closest ASCII counterparts
    'ascii'    => false,

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
    'fonts'    => ['OpenSans-Bold.ttf', 'rockwell.ttf'],

    // List of background colors to be used, randomly selected based on name supplied
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
