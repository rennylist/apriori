<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5fce38a5939e89b8182538dd943906a9
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'RennyPasardesa\\Apriori\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'RennyPasardesa\\Apriori\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5fce38a5939e89b8182538dd943906a9::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5fce38a5939e89b8182538dd943906a9::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit5fce38a5939e89b8182538dd943906a9::$classMap;

        }, null, ClassLoader::class);
    }
}
