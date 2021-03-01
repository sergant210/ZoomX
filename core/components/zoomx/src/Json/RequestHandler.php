<?php
namespace Zoomx\Json;

use Exception;
use FastRoute\Dispatcher;
use Zoomx\AliasRequestHandler;
use Zoomx\Exceptions\HttpException;
use Zoomx\Service;

class RequestHandler extends AliasRequestHandler
{
    /**
     * @return \modResponse
     */
    public function process()
    {
        //$this->clearRequestParam();

        if (zoomx()->getRoutingMode() !== Service::ROUTING_DISABLED) {
            $uri = $this->getRequestUri();
            $uri = $uri === '/' ? $uri : ltrim($uri, '/');

            $this->processRouting($uri);
        }

        return $this->modx->response;
    }

    /**
     * @param string $uri
     */
    public function processRouting($uri)
    {
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $routeInfo = $this->getDispatcher()->dispatch($httpMethod, $uri);
        /** @var Response $response */
        $response = $this->modx->response;
        try {
            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                    abortx(404);
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    $exception = zoomx()->getExceptionClass(405);
                    throw new $exception($routeInfo[1]);
                    break;
                case Dispatcher::FOUND:
                    $result = call_user_func_array($this->getCallback($routeInfo[1]), $routeInfo[2]);
                    $this->handleOutput($result);
                    break;
            }
        } catch (HttpException $e) {
            $response->getErrorHandler();
            $response->setData($e->toArray())->headers->add($e->getHeaders());
        } catch (Exception $e) {
            $this->modx->log(MODX_LOG_LEVEL_ERROR, $e->getMessage());
        }
    }

    /**
     * Handle the result.
     * @param mixed $result
     */
    protected function handleOutput($result)
    {
        if ($result instanceof Response) {
            zoomx()->setResponse($result);
            $this->modx->response = $result;
        }
        if (is_array($result)) {
            $this->modx->response->setData($result);
        }
    }
}