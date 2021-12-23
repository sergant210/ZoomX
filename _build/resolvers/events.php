<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx = $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_UPGRADE:
            if (($eventInit = $modx->getObject('modEvent', ['name' => 'onZoomxInit'])) && $eventInit->name === 'onZoomxInit') {
                $eventInit->set('name', 'OnZoomxInit');
                $eventInit->save();
            }
        case xPDOTransport::ACTION_INSTALL:
            $query = $modx->newQuery('modEvent');
            $query->select('name, groupname');
            $query->where(['groupname' => 'ZoomX']);
            if ($query->prepare() && $query->stmt->execute()) {
                $existEvents = $query->stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }
            $events = ['OnRequestError', 'OnZoomxInit', 'OnBeforeRouteProcess'];
            foreach ($events as $name) {
                if (!isset($existEvents[$name])) {
                    /** @var modEvent $event */
                    $event = $modx->newObject('modEvent');
                    $event->fromArray(['name' => $name, 'service' => 6, 'groupname' => 'ZoomX',], '', true, true);
                    $event->save();
                }
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;
