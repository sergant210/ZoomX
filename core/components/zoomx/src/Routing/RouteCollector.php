<?php

namespace Zoomx\Routing;


class RouteCollector extends \FastRoute\RouteCollector
{
    public function redirect(string $from, string $targetUrl, int $status = 302)
    {
        $data = [
            'redirect' => [
                'targetUrl' => $targetUrl,
                'status' => $status,
            ]
        ];

        $this->addRedirect('GET', $from, ['\Zoomx\RedirectResponse', '__invoke'], $data);
    }

    /**
     * Adds a route to the collection.
     *
     * The syntax used in the $route string depends on the used route parser.
     *
     * @param string|string[] $httpMethod
     * @param string $route
     * @param mixed $handler
     * @param array $routeParams
     */
    public function addRedirect($httpMethod, $route, $handler, array $routeParams = [])
    {
        $route = $this->currentGroupPrefix . $route;
        $routeDatas = $this->routeParser->parse($route);

        foreach ((array) $httpMethod as $method) {
            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRedirect($method, $routeData, $handler, $routeParams);
            }
        }
    }
}