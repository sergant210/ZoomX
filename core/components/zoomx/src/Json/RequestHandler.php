<?php
namespace Zoomx\Json;

use Zoomx\AliasRequestHandler;
use Zoomx\DTO\Error as ErrorData;
use Zoomx\Exceptions\HttpException;
use Zoomx\Exceptions\NotFoundHttpException;
use Zoomx\Service;

class RequestHandler extends AliasRequestHandler
{
    /**
     * @return \modResponse
     */
    public function process()
    {
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
        $output = zoomx('router')->dispatch($uri);

        $this->handleOutput($output);
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

    /**
     * @param ErrorData|array|null $error
     * @throws \ReflectionException
     * @throws \SmartyException
     */
    public function sendErrorPage($error = null)
    {
        if ($error === null) {
            $class = zoomx()->getExceptionClass(404, NotFoundHttpException::class);
            $exception = new $class();
            $error = new ErrorData($exception->toArray());
            $error->object = $exception;
        } elseif (is_array($error)) {
            $error = new ErrorData($error);
        }

        $this->invokeEvent($error);
        $this->sendError($error);
    }

    protected function sendError(ErrorData $error)
    {
        while (ob_get_level() && @ob_end_clean()) {}
        if (!XPDO_CLI_MODE) {
            $response = $this->modx->response;
            $response->setStatusCode($error->code);
            $response->setData([
                'code' => $error->code,
                'title' => $error->title,
                'message' => $error->message,
            ]);
            $headers = $error->object instanceof HttpException ? $error->object->getHeaders() : [500 => $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error'];
            $response->headers->add($headers);
            $response->outputContent();
        } else {
            echo $error->title, "\n", $error->message, "\n";
        }
        exit();
    }
}