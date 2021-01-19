<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx = $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
		case xPDOTransport::ACTION_UPGRADE:
            if ($modx->getCount('modEvent', ['name' => 'OnRequestError']) === 0) {
                /** @var modEvent $event */
                $event = $modx->newObject('modEvent');
                $event->fromArray(
                    [
                        'name' => 'OnRequestError',
                        'service' => 6,
                        'groupname' => 'ZoomX',
                    ],
                    '',
                    true,
                    true
                );
                $event->save();
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;