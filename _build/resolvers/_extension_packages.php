<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx = $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            $ep = $modx->newObject('modExtensionPackage', [
                'namespace' => 'zoomx',
                'name' => 'autoloader',
                'path' => MODX_CORE_PATH . 'components/zoomx/autoload/',
                'service_class' => 'Autoloader',
                'service_name' => 'autoloader',
            ]);

            if (!$ep->save()) {
                $modx->log(modX::LOG_LEVEL_ERROR, 'Extension package ZoomX is not registered.');
            }
            break;
        case xPDOTransport::ACTION_UPGRADE:
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            if ($ep = $modx->getObject('modExtensionPackage', ['namespace' => 'zoomx', 'name' => 'autoloader',])) {
                // @var modExtensionPackage $ep
                $ep->remove();
            }
            break;
    }
}
return true;