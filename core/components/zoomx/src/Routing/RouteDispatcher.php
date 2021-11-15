<?php

namespace Zoomx\Routing;


use FastRoute\Dispatcher\GroupCountBased as FastRouteGroupCountBased;

class RouteDispatcher extends FastRouteGroupCountBased
{
    protected function dispatchVariableRoute($routeData, $uri)
    {
        foreach ($routeData as $data) {
            if (!preg_match($data['regex'], $uri, $matches)) {
                continue;
            }
            [$handler, $varNames] = $data['routeMap'][count($matches)];

            $vars = [];
            $i = 0;
            foreach ($varNames as $varName) {
                $vars[$varName] = $matches[++$i];
            }
            $params['params'] = [];
            $params['params']['redirect'] = $data['redirect'] ?: [];
            $params['params']['view'] = $data['view'] ?: [];

            return [self::FOUND, $handler, $vars, $params['params']];
        }

        return [self::NOT_FOUND];
    }
}
