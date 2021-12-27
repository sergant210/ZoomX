<?php

if (PHP_SAPI !== 'cli') {
    return;
}

require __DIR__.'/config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');

$result = Zoomx\Commands\CommandManager::execute(array_slice($argv, 1));
print_r($result);
