<?php

if (!function_exists('avatar')) {
    function avatar($subject)
    {
        return \Laravolt\Avatar\Avatar::create($subject)->toBase64();
    }
}
