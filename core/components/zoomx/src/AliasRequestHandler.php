<?php
namespace Zoomx;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use modResource;
use modX;
use xPDO;

use function FastRoute\simpleDispatcher;

class AliasRequestHandler extends RequestHandler
{
    /** @var Dispatcher */
    protected $dispatcher;
    /** @var bool Prevent endless recursive calls */
    protected $processing = false;


    /**
     *
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
        $uri = $_SERVER['REQUEST_URI'];
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        if ($uri === $this->modx->getOption('base_url')) {
            $this->modx->resourceIdentifier = (int)$this->modx->getOption('site_start', null, 1);
        } else {
            $uri = ltrim($uri, '/');
        }

        if (zoomx()->getRoutesMode() !== Service::ROUTES_DISABLED) {
            parserx()->setTpl($this->processRoutes($uri));
        }

        return  $this->modx->resourceIdentifier ?? $uri;
    }

    /**
     * @param string $uri
     * @return View|null
     */
    public function processRoutes($uri)
    {
        $modx = $this->modx;
        $output = null;

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $routeInfo = $this->getDispatcher()->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                if (zoomx()->getRoutesMode() === Service::ROUTES_STRICT) {
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
                        : $this->getResourceURI($errorPageId);
                    $this->processing = true;
                    parserx()->setTpl($this->processRoutes($uri));
                    //TODO: Сделать собственную реализацию
                    $this->modx->sendErrorPage();
                }
                return null;
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
                $modx->sendError('', $options);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $request = zoomx()->getRequest();
                $request->setRouteParams($routeInfo[2]);
                /*$this->modx->invokeEvent('OnBeforeRouteHandle', [
                    'uri' => $uri,
                    'request' => $request,
                ]);*/
                $output = call_user_func_array($handler, $request->getRouteParams());
                break;
        }

        return $this->validateOutput($output);
    }

    /**
     * Return a View object.
     * @param $output
     * @return View
     */
    protected function validateOutput($output): View
    {
        return (! $output instanceof View) ? viewx(md5((string)$output))->setContent((string)$output) : $output;
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
        return parent::getResource($resourceId, $options);
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
                }/*, [
            'cacheFile' => $this->getCacheRoutesPath() . '/routes.cache.php',
        ]*/
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
    protected function getResourceURI($id) {
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