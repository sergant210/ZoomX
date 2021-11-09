<?php
namespace Zoomx;

use modX;
use modRequest;
use Zoomx\DTO\Error as ErrorData;


class Request extends modRequest
{
    /** @var RequestHandler  */
    protected $handler;
    /** @var array  */
    protected $routeParams = [];
    /** @var bool  */
    protected $hasRoute = false;

    /**
     * @param modX $modx A reference to the modX instance
     */
    public function __construct(modX $modx)
    {
        parent::__construct($modx);
//        $modx->response = Service::getInstance($modx)->getResponse();
        $this->handler = $this->getRequestHandler();
        $this->getMethod();
    }

    /**
     * Gets the request "intended" method.
     *
     * The method is always an uppercased string.
     *
     * @return string The request method
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

            if ('POST' === $this->method && $this->modx->getOption('zoomx_http_method_override', null, true)) {
                $this->method = strtoupper($_POST['_method'] ?? 'POST');
            }
        }

        return $this->method;
    }

    /**
     * The primary MODX request handler (a.k.a. controller).
     */
    public function handleRequest()
    {
        $this->loadErrorHandler();
        // If enabled, send the X-Powered-By header to identify this site as running MODX, per discussion in #12882
        if ($this->modx->getOption('send_poweredby_header', null, true)) {
            $version = $this->modx->getVersionData();
            header("X-Powered-By: MODX {$version['code_name']}");
        }
        $this->sanitizeRequest();
        $this->modx->invokeEvent('OnHandleRequest');
        if (!$this->modx->checkSiteStatus()) {
            if (zoomx()->getRoutingMode() === Service::ROUTING_STRICT) {
                abortx(503);
            } else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
                if (!$this->modx->resourceIdentifier = $this->modx->getOption('site_unavailable_page', null, 1)) {
                    $this->modx->resource = $this->modx->newObject('modDocument');
                    $this->modx->resource->template = 0;
                    $this->modx->resource->content = $this->modx->getOption('site_unavailable_message');
                    $this->modx->resourceIdentifier = 0;
                }
                $this->getIdRequestHandler();
            }
        } else {
            $this->checkPublishStatus();
            $this->modx->resourceIdentifier = $this->handler->getResourceIdentifier();
        }

        $this->modx->beforeRequest();
        $this->modx->invokeEvent("OnWebPageInit");

        // If the resource should not be loaded.
        if ($this->hasRoute() && !$this->modx->getOption('zoomx_autoload_resource', null, true)) {
            $this->modx->resource = $this->modx->newObject('modDocument', ['content_type' => 0]);
        } elseif (!$this->isApiMode() && !is_object($this->modx->resource)) {
            $this->modx->resource = $this->getResource('', $this->modx->resourceIdentifier);
            if ($this->modx->resource === null) {
                abortx(404);
            }
        }

        $this->prepareResponse();
    }

    /**
     * @param array $options
     * @return bool|void
     */
    public function prepareResponse(array $options = [])
    {
        $this->modx->invokeEvent("OnLoadWebDocument");
        $this->modx->response = $this->modx->response ?? zoomx()->getResponse();
        $this->modx->response->outputContent($options);
    }

    /**
     * {@inheritDoc}
     */
    public function getResource($method, $identifier, array $options = [])
    {
        return $this->handler->getResource($identifier, $options);
    }


    /**
     * @param bool $reload
     * @return RequestHandler
     */

    public function getRequestHandler($reload = false)
    {
        if ($reload) {
            $this->handler = null;
        }

        if (!isset($this->handler)) {
            $this->handler = $this->modx->getOption('friendly_urls', null, false) ? $this->getAliasRequestHandler() : $this->getIdRequestHandler();
            //TODO: checkImplements
        }

        return $this->handler;
    }

    /**
     * @return RequestHandler
     */
    public function getAliasRequestHandler(): RequestHandler
    {
        $class = $this->modx->getOption('zoomx_alias_request_handler_class', null, AliasRequestHandler::class, true);
        return $this->handler = new $class($this->modx);
    }

    /**
     * @return RequestHandler
     */
    public function getIdRequestHandler(): RequestHandler
    {
        $class = $this->modx->getOption('zoomx_id_request_handler_class', null, IdRequestHandler::class, true);
        return $this->handler = new $class($this->modx);
    }

    /**
     * @var array $params
     * @return $this;
     */
    public function setRouteParams(array $params)
    {
        $this->routeParams = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * @var bool|null $value
     * @return bool|$this;
     */
    public function hasRoute($value = null)
    {
        if (!is_bool($value)) {
            return $this->hasRoute;
        }
        $this->hasRoute = $value;
        return $this;
    }

    /**
     * Send the user to a MODX virtual error page.
     * @param ErrorData|array|null $e
     */
    public function sendErrorPage($e = null)
    {
        $this->handler->sendErrorPage($e);
    }

    private function isApiMode()
    {
        return  ($this->modx->response && $this->modx->response instanceof Json\ResponseInterface) ||
                (defined('ZOOMX_API_MODE') && ZOOMX_API_MODE === true);
    }
}