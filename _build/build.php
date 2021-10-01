<?php
set_time_limit(0);
/** @var modX $modx */
$modx = require __DIR__ . '/bootstrap.php';
require __DIR__ . '/src/ZoomXPackage.php';

if (PHP_SAPI !== 'cli') {
    ob_start();
}
$builder = new ZoomXPackage($modx, $modx->config['package_config']);
$builder->process();

if (PHP_SAPI !== 'cli') {
    $builder->queueManager->read([
        'poll_limit' => 1,
        'msg_limit' => 100,
    ]);

    $content = ob_get_clean();
    $time = microtime(true) - $modx->startTime;

    $response = [
        'details' => $content,
        'file' => MODX_CORE_PATH . 'packages/' . $builder->builder->getSignature() . '.transport.zip',
        'total_time' => number_format($time, 4),
        'memory' => number_format(memory_get_usage(true) / 1024, 0, ",", " ") . ' kb',
    ];

    die(json_encode($response));
}