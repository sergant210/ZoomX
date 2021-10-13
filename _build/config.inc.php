<?php

if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__, 3);
    while (!file_exists($path . '/core/config/config.inc.php') && (strlen($path) > 1)) {
        $path = dirname($path);
    }
    define('MODX_CORE_PATH', $path . '/core/');
}

defined('PACKAGE_NAME') or define('PACKAGE_NAME', 'ZoomX');

return [
    /**************************************************************************/
    /*                                                                        */
    /*                      Package name and version                          */
    /*                                                                        */
    /**************************************************************************/
    'name' => PACKAGE_NAME,
    'name_lower' => strtolower(PACKAGE_NAME),
    'version' => '3.0.2',
    'release' => 'beta',

    /**************************************************************************/
    /*                                                                        */
    /*      Install the package immediately after creating the package        */
    /*                                                                        */
    /**************************************************************************/
    'install' => false,

    /**************************************************************************/
    /*                                                                        */
    /*                  Which elements should be updated                      */
    /*                                                                        */
    /**************************************************************************/
    'update' => [
        'chunks' => false,
        'snippets' => false,
        'plugins' => true,
        'menus' => false,
        'permission' => false,
        'policies' => false,
        'policy_templates' => false,
        'resources' => false,
        'settings' => false,
        'templates' => false,
        'widgets' => false,
    ],

    /**************************************************************************/
    /*                                                                        */
    /*            Which elements should be static by default                  */
    /*                                                                        */
    /**************************************************************************/
    'static' => [
        'plugins' => false,
        'snippets' => false,
        'chunks' => false,
    ],

    /**************************************************************************/
    /*                                                                        */
    /*                        Logging Settings                                */
    /*                                                                        */
    /**************************************************************************/
    'log_level' => PHP_SAPI === 'cli' ? 3 : 2,
    'log_target' => PHP_SAPI === 'cli' ? 'ECHO' : 'HTML',
];