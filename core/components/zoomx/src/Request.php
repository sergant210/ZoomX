<?php
namespace Zoomx;

use modX;
use modRequest;

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
        $zoomService = Service::getInstance($modx);
        $zoomService->setRequest($this);
        $this->handler = $this->getRequestHandler();
    }


    /**
     * The primary MODX request handler (a.k.a. controller).
     *
     * @return boolean True if a request is handled without interruption.
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
            header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
            if (!$this->modx->resourceIdentifier = $this->modx->getOption('site_unavailable_page', null, 1)) {
                $this->modx->resource = $this->modx->newObject('modDocument');
                $this->modx->resource->template = 0;
                $this->modx->resource->content = $this->modx->getOption('site_unavailable_message');
                $this->modx->resourceIdentifier = 0;
            }
            $this->getIdRequestHandler();
        } else {
            $this->checkPublishStatus();
            $this->modx->resourceIdentifier = $this->handler->getResourceIdentifier();
        }

        $this->modx->beforeRequest();
        $this->modx->invokeEvent("OnWebPageInit");

        if (!is_object($this->modx->resource) && !($this->modx->resource = $this->handler->getResource($this->modx->resourceIdentifier))) {

            $this->modx->sendErrorPage();
        }

        return $this->prepareResponse();
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
        if (isset($this->handler) && $reload) {
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
        $class = $this->modx->getOption('zoomx_alias_request_handler', null, AliasRequestHandler::class, true);
        return $this->handler = new $class($this->modx);
    }

    /**
     * @return RequestHandler
     */
    public function getIdRequestHandler(): RequestHandler
    {
        $class = $this->modx->getOption('zoomx_id_request_handler', null, IdRequestHandler::class, true);
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
     * @var array $params
     * @return bool|$this;
     */
    public function hasRoute($value = null)
    {
        if ($value === null || !is_bool($value)) {
            return $this->hasRoute;
        }
        $this->hasRoute = $value;
        return $this;
    }
}