<?php

if (!function_exists('avatar')) {
    function avatar($subject)
    {
        return app()->make('avatar')->create($subject)->toBase64();
    }
}
