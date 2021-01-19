<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx = $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            break;
        case xPDOTransport::ACTION_UPGRADE:
            if ($setting = $modx->getObject('modSystemSetting', ['key' => 'zoomx_routes_mode'])) {
                $setting->set('key', 'zoomx_routing_mode');
                if (!$setting->save()) {
                    $modx->log(modX::LOG_LEVEL_ERROR, 'Can\'t update the system setting "zoomx_routes_mode".');
                }
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;