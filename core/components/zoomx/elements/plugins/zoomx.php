<?php
/** @var modX $modx */
if ($modx->event->name === 'OnMODXInit') {
    $corePath = $modx->getOption('zoomx_core_path', null, MODX_CORE_PATH . 'components/zoomx/');
    include_once $corePath . 'vendor/autoload.php';
    $zoomx = Zoomx\Service::getInstance($modx);
    if ($modx->context->key !== 'mgr') {
        $modx->request = $zoomx->getRequest();
        $modx->response = $zoomx->getResponse();
    }
    return;
}

try {
    $parser = parserx();
} catch (LogicException $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
    return;
}

switch ($modx->event->name) {
    case 'OnSiteRefresh':
        $parser->refresh();
        break;
    case 'OnCacheUpdate':
        $parser->refresh('cache');
        break;
}