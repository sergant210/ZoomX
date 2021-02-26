<?php
/** @var modX $modx */
if ($modx->event->name === 'OnMODXInit') {
    $corePath = $modx->getOption('zoomx_core_path', null, MODX_CORE_PATH . 'components/zoomx/');
    include_once $corePath . 'vendor/autoload.php';
    $zoomx = Zoomx\Service::getInstance($modx);
    if ($modx->context->key !== 'mgr' && PHP_SAPI  !== 'cli' && (!defined('MODX_API_MODE') || !MODX_API_MODE)) {
        $modx->request = $zoomx->shouldBeJson() ? $zoomx->getJsonRequest() : $zoomx->getRequest();
    }
    return;
}

try {
    $parser = parserx();
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
    return;
}

switch ($modx->event->name) {
    case 'OnSiteRefresh':
        $parser->refresh();
        break;
    case 'OnCacheUpdate':
        $parser->refresh(['cache', 'compiled']);
        break;
}