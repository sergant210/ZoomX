<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx = $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
		case xPDOTransport::ACTION_UPGRADE:
            $file = MODX_CORE_PATH . 'config/exceptions.php';
            if (!file_exists($file)) {
                $content = '<?php
				
return [
];';
                file_put_contents($file, $content);
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;