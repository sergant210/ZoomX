<?php

namespace Zoomx\Routing;

class DataGenerator extends \FastRoute\DataGenerator\GroupCountBased
{
    protected $routeParams;

    /**
     * @param string $httpMethod
     * @param array $routeData
     * @param mixed $handler
     * @param array $routeParams
     */
    public function addRedirect($httpMethod, $routeData, $handler, $routeParams)
    {
        $this->routeParams = $routeParams;
        $this->addRoute($httpMethod, $routeData, $handler);
    }

    protected function processChunk($regexToRoutesMap)
    {
        $routeMap = [];
        $regexes = [];
        $numGroups = 0;
        foreach ($regexToRoutesMap as $regex => $route) {
            $numVariables = count($route->variables);
            $numGroups = max($numGroups, $numVariables);

            $regexes[] = $regex . str_repeat('()', $numGroups - $numVariables);
            $routeMap[$numGroups + 1] = [$route->handler, $route->variables];

            ++$numGroups;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        $output = ['regex' => $regex, 'routeMap' => $routeMap, ];
        if (null !== $this->routeParams['redirect']) {
            $output += ['redirect' => $this->routeParams['redirect']];
        }
        if (null !== $this->routeParams['view']) {
            $output += ['view' => $this->routeParams['view']];
        }

        return $output;
    }
}
