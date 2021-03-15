<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite31477fb0bc1f9c321e9169a838fed1e
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PelemanProductUploader\\' => 23,
        ),
        'A' => 
        array (
            'Automattic\\WooCommerce\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PelemanProductUploader\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
        'Automattic\\WooCommerce\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce',
        ),
    );

    public static $classMap = array (
        'Automattic\\WooCommerce\\Client' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/Client.php',
        'Automattic\\WooCommerce\\HttpClient\\BasicAuth' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/BasicAuth.php',
        'Automattic\\WooCommerce\\HttpClient\\HttpClient' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/HttpClient.php',
        'Automattic\\WooCommerce\\HttpClient\\HttpClientException' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/HttpClientException.php',
        'Automattic\\WooCommerce\\HttpClient\\OAuth' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/OAuth.php',
        'Automattic\\WooCommerce\\HttpClient\\Options' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/Options.php',
        'Automattic\\WooCommerce\\HttpClient\\Request' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/Request.php',
        'Automattic\\WooCommerce\\HttpClient\\Response' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/Response.php',
        'PelemanProductUploader\\Admin\\PpuAdmin' => __DIR__ . '/../..' . '/Admin/PpuAdmin.php',
        'PelemanProductUploader\\Includes\\Plugin' => __DIR__ . '/../..' . '/Includes/Plugin.php',
        'PelemanProductUploader\\Includes\\PpuActivator' => __DIR__ . '/../..' . '/Includes/PpuActivator.php',
        'PelemanProductUploader\\Includes\\PpuDeactivator' => __DIR__ . '/../..' . '/Includes/PpuDeactivator.php',
        'PelemanProductUploader\\Includes\\PpuI18n' => __DIR__ . '/../..' . '/Includes/PpuI18n.php',
        'PelemanProductUploader\\Includes\\PpuLoader' => __DIR__ . '/../..' . '/Includes/PpuLoader.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite31477fb0bc1f9c321e9169a838fed1e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite31477fb0bc1f9c321e9169a838fed1e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInite31477fb0bc1f9c321e9169a838fed1e::$classMap;

        }, null, ClassLoader::class);
    }
}
