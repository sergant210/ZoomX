<?php
namespace Zoomx\Json;

use FastRoute\Dispatcher;
use Zoomx\AliasRequestHandler;
use modResponse;
use Zoomx\Service;

class RequestHandler extends AliasRequestHandler
{
    /**
     * @return Response
     */
    public function process()
    {
        $this->modx->response = zoomx()->getJsonResponse();
        $this->clearRequestParam();

        if (zoomx()->getRoutingMode() === Service::ROUTING_DISABLED) {
            return $this->modx->response;
        }
        $uri = $this->getRequestUri();
        $uri = $uri === '/' ? $uri : ltrim($uri, '/');

        return $this->processRouting($uri);
    }

    /**
     * @param string $uri
     * @return Response|modResponse
     */
    public function processRouting($uri)
    {
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $routeInfo = $this->getDispatcher()->dispatch($httpMethod, $uri);
        $response = $this->modx->response;

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $response->getErrorHandler();
                $response->header(404, $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                $response->setData([
                   'status' => '404',
                   'code' => '404',
                   'title' => 'Not Found.',
                   'detail' => 'No route is found.',
                ]);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $response->getErrorHandler();
                $response->header(405, $_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
                $allowedMethods = implode(', ', $routeInfo[1]);
                $response->setData([
                   'status' => '405',
                   'code' => '405',
                   'title' => 'Method Not Allowed.',
                   'detail' => "Method '{$httpMethod}' is not allowed. Use one of these - [{$allowedMethods}].",
                ]);
                break;
            case Dispatcher::FOUND:
                $data = call_user_func_array($this->getCallback($routeInfo[1]), $routeInfo[2]);

                if ($data instanceof Response) {
                    zoomx()->setResponse($data);
                    $this->modx->response = $data;
                }
                if (is_array($data)) {
                    $this->modx->response->setData($data);
                }
                break;
        }

        return $response;
    }
}