<?php
namespace Zoomx\Json;

use modX;
use modRequest;
use Zoomx\Exceptions\ServiceUnavailableHttpException;
use Zoomx\Service;

class Request extends modRequest
{
    /** @var RequestHandler  */
    protected $handler;
    /** @var array  */
    protected $routeParams = [];

    /**
     * @param modX $modx A reference to the modX instance
     */
    public function __construct(modX $modx)
    {
        parent::__construct($modx);
        $modx->response = Service::getInstance($modx)->getJsonResponse();
        //$zoomService->setRequest($this);
        $this->handler = $this->getRequestHandler();
        $this->getMethod();
    }

    /**
     * Gets the request method.
     *
     * The method is always an uppercased string.
     *
     * @return string The request method
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        }

        return $this->method;
    }

    /**
     * The primary MODX request handler (a.k.a. controller).
     *
     */
    public function handleRequest()
    {
        $this->loadErrorHandler();
        $this->sanitizeRequest();
        $this->modx->invokeEvent('OnHandleRequest');

        if (!$this->modx->checkSiteStatus()) {
            try {
                abortx(503);
            } catch (ServiceUnavailableHttpException $e) {
                /** @var Response $response */
                $response = $this->modx->response;
                $response->getErrorHandler();
                $response->setData($e->toArray())->headers->add($e->getHeaders());
                $response->outputContent();
            }
        }

        $this->prepareResponse();
    }

    /**
     * {@inheritDoc}
     */
    public function prepareResponse(array $options = [])
    {
        $response = $this->handler->process();
        $response->outputContent($options);
    }

    /**
     * @return RequestHandler
     */

    public function getRequestHandler()
    {
        if (!isset($this->handler)) {
            $class = $this->modx->getOption('zoomx_json_request_handler', null, RequestHandler::class, true);
            $this->handler = new $class($this->modx);
        }
        return $this->handler;
    }
}