<?php

if (!file_exists(__DIR__ . '/config.inc.php')) {
    exit('Файл настроек "config.inc.php" не найден!');
}
$config = require(__DIR__ . '/config.inc.php');

/** @noinspection PhpIncludeInspection */
require(MODX_CORE_PATH . 'model/modx/modx.class.php');
/** @var modX $modx */
$modx = new modX();
$modx->initialize('mgr');
$modx->getService('error', 'error.modError');
$modx->startTime = microtime(true);
$modx->config['package_config'] = $config;

return $modx;