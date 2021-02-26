<?php

if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__, 3);
    while (!file_exists($path . '/core/config/config.inc.php') && (strlen($path) > 1)) {
        $path = dirname($path);
    }
    define('MODX_CORE_PATH', $path . '/core/');
}

return [
    'name' => 'ZoomX',
    'name_lower' => 'zoomx',
    'version' => '2.1.0',
    'release' => 'beta',
    // Install package to site right after build
    'install' => false,
    // Which elements should be updated on package upgrade
    'update' => [
        'plugins' => true,
        'chunks' => false,
        'menus' => false,
        'permission' => false,
        'policies' => false,
        'policy_templates' => false,
        'resources' => false,
        'settings' => false,
        'snippets' => false,
        'templates' => false,
        'widgets' => false,
    ],
    // Which elements should be static by default
    'static' => [
        'plugins' => false,
        'snippets' => false,
        'chunks' => false,
    ],
    // Log settings
    'log_level' => !empty($_REQUEST['download']) ? 0 : 3,
    'log_target' => php_sapi_name() === 'cli' ? 'ECHO' : 'HTML',
    // Download transport.zip after build
    'download' => !empty($_REQUEST['download']),
];