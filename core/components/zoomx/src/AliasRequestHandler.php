<?php
namespace Zoomx;

use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use modResource;
use modResponse;
use modX;
use \Zoomx\Json\Response as JsonResponse;

use function FastRoute\simpleDispatcher;

class AliasRequestHandler extends RequestHandler
{
    /** @var Dispatcher */
    protected $dispatcher;
    /** @var bool Prevent endless recursive calls */
    protected $processing = false;


    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        $this->modx->resourceMethod = "alias";
    }

    /**
     * @return int|string|null
     */
    public function getResourceIdentifier()
    {
        $uri = $this->getRequestUri();
        if ($uri === $this->modx->getOption('base_url')) {
            $this->modx->resourceIdentifier = (int)$this->modx->getOption('site_start', null, 1);
        } else {
            $uri = ltrim($uri, '/');
        }
        if (zoomx()->getRoutingMode() !== Service::ROUTING_DISABLED) {
            $this->processRouting($uri);
        }

        return $this->modx->resourceIdentifier ?? $uri;
    }

    /**
     * @param string $uri
     * @return Response|modResponse
     */
    public function processRouting($uri)
    {
        $output = null;

        $httpMethod = $this->modx->request->method;
        $routeInfo = $this->getDispatcher()->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                if (zoomx()->getRoutingMode() === Service::ROUTING_STRICT) {
                    if ($this->processing) {
                        if ($defaultTpl = trim($this->modx->getOption('zoomx_default_tpl'))) {
                            $output = viewx($defaultTpl);
                        }
                        $this->processing = false;
                        break;
                    }
                    $errorPageId = (int)$this->modx->getOption('error_page', null, $this->modx->getOption('site_start'));
                    $uri = $errorPageId === (int)$this->modx->getOption('site_start')
                        ? $this->modx->getOption('base_url')
                        : $this->getResourceUri($errorPageId);
                    $this->processing = true;
                    $this->processRouting($uri);
                    //TODO: Сделать собственную реализацию
                    $this->modx->sendErrorPage();
                }
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = implode(', ', $routeInfo[1]);
                //TODO: Сделать через собственный метод sendError
                $options = [
                    'error_pagetitle' => 'Error 405: Method Not Allowed',
                    'error_message' => "<h1>Method Not Allowed!</h1><p>Method '{$httpMethod}' is not allowed. Use one of these [{$allowedMethods}].</p>",
                    'error_header' => $_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
                $this->modx->sendError('', $options);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                /** @var Request $request */
                $request = zoomx()->getRequest()->hasRoute(true)->setRouteParams($routeInfo[2]);

                /*$this->modx->invokeEvent('OnBeforeRouteHandle', [
                    'uri' => $uri,
                    'request' => $request,
                ]);*/
                try {
                    $output = call_user_func_array($this->getCallback($handler), $request->getRouteParams());
                } catch (Exception $e) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
                }
                break;
        }

        return $this->handleOutput($output);
    }

    /**
     * @param callable|string $handler
     * @return string|array
     * @throws Exception
     */
    protected function getCallback($handler)
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $method = empty($method) ? 'index' : $method;
            $handler = [new $class($this->modx), $method];
        }

        return $handler;
    }

    /**
     * Handle the result.
     * @param mixed $output
     * @return Response|modResponse
     */
    protected function handleOutput($output)
    {
        if (is_array($output)) {
            $this->modx->response = zoomx()->getJsonResponse()->setData($output);
        } elseif ($output instanceof JsonResponse) {
            $this->modx->response = $output;
        } elseif (! $output instanceof View) {
            $content = (string)$output;
            parserx()->setTpl(viewx(md5($content))->setContent($content));
        } else {
            parserx()->setTpl($output);
        }

        return $this->modx->response = $this->modx->response ?? zoomx()->getResponse();
    }

    /**
     * Gets a requested resource and all required data.
     *
     * @param string|integer $identifier The identifier with which to search.
     * @param array $options An array of options for the resource fetching
     * @return modResource|null The requested modResource instance or request is forwarded to the error page, or unauthorized page.
     */
    public function getResource($identifier, array $options = [])
    {
        if (is_int($identifier)) {
            return parent::getResource($identifier, $options);
        }

        return $this->getResourceByAlias($identifier, '', $options);
    }


    /**
     * @param string $uri
     * @param string $context
     * @param array $options
     * @return \modResource|null
     */
    public function getResourceByAlias($uri, $context = '', array $options = [])
    {
        if (empty($uri) || !is_string($uri)) {
            return null;
        }
        $this->clearRequestParam();
        $resourceId = null;
        if (empty($context) && isset($this->modx->context)) {
            $context = $this->modx->context->get('key');
        }

        if (!empty($context) && !empty($uri)) {
            if ($this->modx->context->get('key') === $context) {
                $resourceId = $this->modx->getOption('cache_alias_map', null, false)
                && is_array($this->modx->aliasMap)
                && isset($this->modx->aliasMap[$uri])
                    ? (int)$this->modx->aliasMap[$uri]
                    : null;
            } elseif ($ctx = $this->modx->getContext($context)) {
                $resourceId = $ctx->getOption('cache_alias_map', false)
                && is_array($ctx->aliasMap)
                && isset($ctx->aliasMap[$uri])
                    ? (int)$ctx->aliasMap[$uri]
                    : null;
            }

            if (!$resourceId) {
                $this->resource = $this->modx->getObject('modResource', ['uri' => $uri, 'context_key' => $context, 'deleted' => false]);
                $resourceId = is_object($this->resource) ? $this->resource->get('id') : null;
            }
        }
        return $resourceId ? parent::getResource($resourceId, $options) : $resourceId;
    }

    protected function getDispatcher()
    {
        $modx = $this->modx;
        if (!$this->dispatcher) {
            $this->dispatcher = simpleDispatcher(
                static function (RouteCollector $router) use ($modx) {
                    if (file_exists(MODX_CORE_PATH . MODX_CONFIG_KEY . '/routes.php')) {
                        include_once MODX_CORE_PATH . MODX_CONFIG_KEY . '/routes.php';
                    }
                }
            );
        }
        return $this->dispatcher;
    }

    protected function getCacheRoutesPath()
    {
        $path = $this->modx->getCachePath() . 'zoomx/routes';
        if (!is_dir($path)) {
            $this->modx->getCacheManager();
            $this->modx->cacheManager->writeTree($path);
        }
        return $path;
    }

    protected function clearRequestParam()
    {
        $param = $this->modx->getOption('request_param_alias', null, 'q');
        unset($_GET[$param], $_REQUEST[$param]);
    }

    /**
     * Get a Resource URI in this Context by id.
     *
     * @param integer $id The integer id of the Resource.
     * @return string The URI of the Resource.
     */
    protected function getResourceUri($id) {
        $uri = '';

        if ($this->modx->getOption('cache_alias_map') && isset($this->aliasMap)) {
            $uri = array_search($id, $this->aliasMap);
        }
        if (empty($uri) && $this->resource = parent::getResource($id)) {
            $uri = $this->resource->get('uri');
        }

        return $uri;
    }
}