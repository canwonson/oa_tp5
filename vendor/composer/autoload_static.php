<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita04d3c6b3715da0a0d6ef510751d7e4f
{
    public static $prefixLengthsPsr4 = array (
        't' => 
        array (
            'think\\composer\\' => 15,
            'think\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'think\\composer\\' => 
        array (
            0 => __DIR__ . '/..' . '/topthink/think-installer/src',
        ),
        'think\\' => 
        array (
            0 => __DIR__ . '/../..' . '/thinkphp/library/think',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita04d3c6b3715da0a0d6ef510751d7e4f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita04d3c6b3715da0a0d6ef510751d7e4f::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
