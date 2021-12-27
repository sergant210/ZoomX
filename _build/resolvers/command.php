<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

if ($transport->xpdo) {
    $modx = $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $target = MODX_BASE_PATH . 'command.php';
            $source = MODX_CORE_PATH . 'components/zoomx/command/command.php';
            copy($source, $target);
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            $file = MODX_BASE_PATH . 'command.php';
            @unlink($file);
            break;
    }
}
return true;