<?php
namespace Zoomx;

use Exception;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use modResource;
use Zoomx\Exceptions\HttpException;
use Zoomx\Exceptions\NotFoundHttpException;
use \Zoomx\Json\Response as JsonResponse;

use function FastRoute\simpleDispatcher;

class AliasRequestHandler extends RequestHandler
{
    /** @var Dispatcher */
    protected $dispatcher;


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
        if (!file_exists(MODX_CORE_PATH . MODX_CONFIG_KEY . '/routes.php')) {
            $this->modx->setOption('zoomx_routing_mode', Service::ROUTING_DISABLED);
        }
        if (zoomx()->getRoutingMode() !== Service::ROUTING_DISABLED) {
            $this->processRouting($uri);
        }

        return $this->modx->resourceIdentifier ?? $uri;
    }

    /**
     * @param string $uri
     */
    public function processRouting($uri)
    {
        $output = null;

        $httpMethod = $this->modx->request->method;
        $routeInfo = $this->getDispatcher()->dispatch($httpMethod, $uri);
        try {
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
                    /** @var Request $request */
                    $request = zoomx()->getRequest()->hasRoute(true)->setRouteParams($routeInfo[2]);

                    /*$this->modx->invokeEvent('OnBeforeRouteHandle', [
                        'uri' => $uri,
                        'request' => $request,
                    ]);*/
                    $output = call_user_func_array($this->getCallback($handler), $request->getRouteParams());
                    break;
            }
        } catch (HttpException $e) {
            $this->sendErrorPage($e);
        } catch (Exception $e) {
            $this->modx->log(MODX_LOG_LEVEL_ERROR, $e->getMessage());
            $this->modx->sendError('fatal');
        }

        $this->handleOutput($output);
    }

    /**
     * @param array|callable $handler
     * @return string|array
     * @throws Exception
     */
    protected function getCallback($handler)
    {
        $handler = is_string($handler) ? [$handler] : $handler;
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
     */
    protected function handleOutput($output)
    {
        if (is_array($output)) {
            $this->modx->response = zoomx()->getJsonResponse()->setData($output);
        } elseif ($output instanceof JsonResponse) {
            $this->modx->response = $output;
        } elseif (! $output instanceof View) {
            if ($output !== null) {
                $content = (string)$output;
                parserx()->setTpl(viewx(md5($content))->setContent($content));
                $this->modx->resource = $this->getResource((int)$this->modx->getOption('site_start', null, 1));
            }
        } else {
            parserx()->setTpl($output);
        }

        $this->modx->response = $this->modx->response ?? zoomx()->getResponse();
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
        $context = $options['context'] ?? $this->modx->context->get('key');

        return $this->getResourceByAlias($identifier, $context, $options);
    }


    /**
     * @param string $uri
     * @param string $context
     * @param array $options
     * @return modResource|null
     */
    public function getResourceByAlias($uri, $context = '', array $options = [])
    {
        if (empty($uri) || !is_string($uri)) {
            return null;
        }

        //$this->clearRequestParam();
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
        if (!$this->dispatcher) {
            $modx = $this->modx;
            $requestHandler = $this;
            $this->dispatcher = simpleDispatcher(
                static function (RouteCollector $router) use ($modx, $requestHandler) {
                    try {
                        include_once MODX_CORE_PATH . MODX_CONFIG_KEY . '/routes.php';
                    } catch (HttpException $e) {
                        $requestHandler->sendErrorPage($e);
                    } catch (Exception $e) {
                        $modx->sendError('fatal', ['error_message' => $e->getMessage()]);
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

    /**
     * @param \Zoomx\Exceptions\HttpException|null $e
     */
    public function sendErrorPage(HttpException $e = null)
    {
        if ($e === null) {
            $class = zoomx()->getExceptionClass(404, NotFoundHttpException::class);
            $e = new $class;
        }
        if (zoomx()->getRoutingMode() === Service::ROUTING_STRICT || $this->modx->request->hasRoute()) {
            $this->invokeEvent($e);
            $tpl = $this->getErrorTpl($e->getStatusCode(), $this->modx->getOption('zoomx_default_tpl', null, 'error.tpl'));
            parserx()->setTpl($tpl, $e->toArray());
            $this->sendError($e);
        } else {
            //MODX mode
            switch ($e->getStatusCode()) {
            	case 401:
            	case 403:
                    $this->modx->sendUnauthorizedPage();
            		break;
                case 404:
                    $this->modx->sendErrorPage();
            		break;
                default:
                    $this->modx->sendError('', [
                        'error_pagetitle' => $e->getTitle(),
                        'error_message' => $e->getMessage(),
                    ]);
            }
        }
    }

    protected function sendError(HttpException $e)
    {
        while (ob_get_level() && @ob_end_clean()) {}
        if (!XPDO_CLI_MODE) {
            foreach ($e->getHeaders() as $header => $value) {
                $header = is_int($header) ? $value : "{$header}: {$value}";
                header($header);
            }
            echo parserx()->process();
        } else {
            echo $e->getTitle(), "\n", $e->getMessage(), "\n";
        }
        exit();
    }

    protected function invokeEvent(HttpException $e)
    {
        switch ($e->getStatusCode()) {
            case 401:
            case 403:
                $event = 'OnPageUnauthorized';
                break;
            case 404:
                $event = 'OnPageNotFound';
                break;
            default:
                $event = 'OnRequestError';
        }
        $this->modx->invokeEvent($event, [
            'error_type' => $e->getStatusCode(),
            'error_pagetitle' => $e->getTitle(),
            'error_message' => $e->getMessage(),
            'e' => $e,
        ]);
    }

    private function getErrorTpl($code, $default = null)
    {
        $ext = $this->modx->getOption('zoomx_template_extension', null, 'tpl');
        $tpl = $code . (!empty($ext) ? ".{$ext}" : '');

        return parserx()->templateExists($tpl) ? $tpl : $default;
    }
}