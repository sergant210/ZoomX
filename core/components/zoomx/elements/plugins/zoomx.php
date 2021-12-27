<?php
/** @var modX $modx */
if ($modx->event->name === 'OnMODXInit') {
    $corePath = $modx->getOption('zoomx_core_path', null, MODX_CORE_PATH . 'components/zoomx/');
    include_once $corePath . 'vendor/autoload.php';
    Zoomx\Service::getInstance($modx)->initialize();
    return;
}

try {
    $parser = parserx();
} catch (Throwable $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
    return;
}

switch ($modx->event->name) {
    case 'OnSiteRefresh':
        $parser->refresh();
        break;
    case 'OnCacheUpdate':
        zoomx()->getCacheManager()->refresh(['zoomx']);
        break;
}