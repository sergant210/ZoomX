<?php

namespace Zoomx\Routing;


use FastRoute\Dispatcher;
use Zoomx\Request;
use Zoomx\Service;
use modX;

use function FastRoute\cachedDispatcher;

class Router
{
    /** @var array */
    protected $routeVars;
    /** @var array */
    protected $routeParams;
    /** @var string|array|callable  Route handler */
    protected $handler;
    /** @var Dispatcher */
    protected $dispatcher;
    /** @var modX  */
    protected $modx;
    /** @var array $config */
    protected $config = [];

    /**
     * @param modX $modx
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $this->config = $config + [
                'route_cache_file' => 'route.cache.php',
                'route_map_file' => 'route.map.php',
                'controller_namespace' => $modx->getOption('zoomx_controller_namespace', null, 'Zoomx\\Controllers\\'),
            ];
    }

    /**
     * Get or set a config key.
     * @param string|array $key
     * @param null|mixed $default
     * @return mixed|self
     */
    public function config($key, $default = null)
    {
        if (is_string($key)) {
            return $this->config[$key] ?? $default;
        }
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->config[$k] = $v;
            }
        }
        return $this;
    }

    /**
     * Add route variables (specified in curly braces).
     * @param array $vars
     * @return $this
     */
    public function setRouteVars(array $vars)
    {
        $this->routeVars = $vars;
        return $this;
    }

    /**
     * Get the route variables.
     * @return array
     */
    public function getRouteVars()
    {
        return $this->routeVars;
    }

    /**
     * Add route parameters from redirect and view route methods.
     * @param array $params
     * @return $this
     */
    public function setRouteParams(array $params)
    {
        $this->routeParams = $params;
        return $this;
    }

    /**
     * Get the route parameters.
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * Set the current route handler.
     * @param $handler
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Get the route handler.
     * @return array|callable|string
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Precess the current route.
     * @param string $uri
     * @return false|mixed|null
     */
    public function process(string $uri)
    {
        $output = null;
        $httpMethod = $this->modx->request->method;
        $routeInfo = $this->getDispatcher()->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                if (zoomx()->getRoutingMode() === Service::ROUTING_STRICT) {
                    abortx(404);
                }
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $exception = zoomx()->getExceptionClass(405);
                throw new $exception($routeInfo[1]);
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $this->setRouteVars($routeInfo[2])->setRouteParams($routeInfo[3] ?? [])->setHandler($handler);
                zoomx()->getRequest()->hasRoute(true);

                $this->modx->invokeEvent('OnBeforeRouteProcess', [
                    'uri' => $uri,
                    'router' => $this,
                ]);

                $output = call_user_func_array($this->getCallback($handler), $this->getRouteVars());
                break;
        }

        return $output;
    }

    protected function getDispatcher()
    {
        if (!$this->dispatcher) {
            $modx = $this->modx;
            if ($modx->getOption('zoomx_cache_routes', null, false)) {
                $this->validateCache();
            }
            $this->dispatcher = cachedDispatcher(
                static function (RouteCollector $router) use ($modx) {
                    include_once MODX_CORE_PATH . MODX_CONFIG_KEY . '/routes.php';
                },
                [
                    'cacheFile' => $this->getCachePath() . $this->config['route_cache_file'],
                    'cacheDisabled' => !$modx->getOption('zoomx_cache_routes', null, false),
                    'routeCollector' => RouteCollector::class,
                    'dispatcher' => RouteDispatcher::class,
                    'dataGenerator' => DataGenerator::class,
                ]
            );
        }

        return $this->dispatcher;
    }

    /**
     * @param string|array|callable $handler
     * @return string|array
     * @throws Exception
     */
    protected function getCallback($handler)
    {
        $handler = $this->getFullQualifiedName($handler);

        if (is_array($handler)) {
            [$class, $method] = $handler;
            if (empty($method)) {
                $method = method_exists($class, '__invoke') ? '__invoke' : 'index';
            }
            $method = empty($method) ? 'index' : $method;
            $handler = [new $class($this->modx), $method];
        }

        return $handler;
    }

    private function getFullQualifiedName($handler)
    {
        if (is_string($handler)) {
            $FQN = $handler;
        } elseif (is_array($handler)) {
            $FQN = $handler[0];
        } else {
            return $handler;
        }
        if (
            !empty($this->config['controller_namespace']) &&
            $FQN[0] !== '\\' &&
            strpos($FQN, $this->config['controller_namespace']) !== 0
        ) {
            $FQN = $this->config['controller_namespace'] . $FQN;
        }

        return [$FQN];
    }

    private function validateCache()
    {

        $routesHash = md5_file(MODX_CORE_PATH . MODX_CONFIG_KEY . '/routes.php');
        if (file_exists($map = $this->getCachePath() . $this->config['route_map_file'])) {
            $cachedHash = include $map;
            $file = $this->getCachePath() . $this->config['route_cache_file'];
            if ($cachedHash !== $routesHash && file_exists($file)) {
                @unlink($file);
            }
        }

        if (null === $cachedHash || $cachedHash !== $routesHash) {
            file_put_contents($map, "<?php return '$routesHash';");
        }
    }

    protected function getCachePath()
    {
        return $this->modx->getCachePath() . 'zoomx/';
    }
}