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

            return [self::FOUND, $handler, $vars, $this->getRouteParameters($data)];
        }

        return [self::NOT_FOUND];
    }

    public function dispatch($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            $data = $this->staticRouteMap[$httpMethod][$uri];

            return [self::FOUND, $data['handler'], [], $this->getRouteParameters($data)];
        }

        return parent::dispatch($httpMethod, $uri);
    }

    protected function getRouteParameters(array $data = [])
    {
        $routeParams = [];
        if (isset($data['redirect'])) {
            $routeParams['redirect'] = $data['redirect'];
        }
        if (isset($data['view'])) {
            $routeParams['view'] = $data['view'];
        }

        return $routeParams;
    }
}
