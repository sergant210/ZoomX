<?php

ob_implicit_flush();
$start = microtime(true);
/** @var array $config */
if (!file_exists(__DIR__ . '/config.inc.php')) {
    exit('Файл настроек "config.inc.php" не найден!');
}
$config = require(__DIR__ . '/config.inc.php');

require_once __DIR__ . '/src/ZoomXPackage.php';

class_exists(Smarty::class) or require MODX_CORE_PATH . 'model/smarty/Smarty.class.php';

$install = new ZoomXPackage(MODX_CORE_PATH, $config);
$install->process();

$time = microtime(true) - $start;
echo 'Время: ' . number_format($time, 4) . ' сек.';