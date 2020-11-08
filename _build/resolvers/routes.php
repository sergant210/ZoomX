<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx = $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            $routeFile = MODX_CORE_PATH . 'config/routes.php';
            if (!file_exists($routeFile)) {
                $content = '<?php
/** @var FastRoute\RouteCollector  $router */
/** @var modX  $modx */
/*
$router->get(\'/\', function() use ($modx) {
    return viewx(\'index.tpl\');
});
$router->get(\'404\', function() {
    return viewx(\'404.tpl\');
});
*/';

                file_put_contents($routeFile, $content);
            }
            break;
        case xPDOTransport::ACTION_UPGRADE:
        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;