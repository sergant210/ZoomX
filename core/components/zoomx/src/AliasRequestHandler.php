<?php
namespace Zoomx;

use Error;
use Exception;
use FastRoute\Dispatcher;
use modResource;
use modResponse;
use Zoomx\DTO\Error as ErrorData;
use Zoomx\Exceptions\HttpException;
use Zoomx\Exceptions\NotFoundHttpException;

use function FastRoute\cachedDispatcher;

class AliasRequestHandler extends RequestHandler
{
    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        $this->modx->resourceMethod = "alias";
    }

    /**
     * @return int|string|null
     * @throws \ReflectionException
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
     * @throws \ReflectionException
     */
    public function processRouting($uri)
    {
        $output = zoomx('router')->process($uri);

        $this->handleOutput($output);
    }

    /**
     * Handle the result.
     * @param mixed $output
     * @throws \ReflectionException
     */
    protected function handleOutput($output)
    {
        if (is_array($output)) {
            $this->modx->response = zoomx()->getJsonResponse()->setData($output);
        } elseif ($output instanceof modResponse) {
            $this->modx->response = $output;
        } elseif (! $output instanceof View) {
            if ($output !== null) {
                $content = (string)$output;
                parserx()->setTpl(viewx(md5($content))->setContent($content));
            }
        } else {
            // $output instanceof View
            parserx()->setTpl($output);
        }

        $this->modx->response = $this->modx->response ?? zoomx()->getResponse();
    }

    /**
     * Gets a requested resource and all required data.
     *
     * @param string|int $identifier The identifier with which to search.
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
                $criteria = $this->modx->newQuery('modResource');
                $criteria->where(['uri' => $uri, 'context_key' => $context, 'deleted' => 0]);
                if ($this->modx->getSessionState() !== \modX::SESSION_STATE_INITIALIZED || !$this->modx->hasPermission('view_unpublished')) {
                    $criteria->where(['published' => 1]);
                }
                $this->resource = $this->modx->getObject('modResource', $criteria);
                $resourceId = isset($this->resource) ? $this->resource->get('id') : null;
            }
        }

        return $resourceId ? parent::getResource($resourceId, $options) : $resourceId;
    }

    protected function clearRequestParam()
    {
        $param = $this->modx->getOption('request_param_alias', null, 'q');
        unset($_GET[$param], $_REQUEST[$param]);
    }

    /**
     * Get a Resource URI in this Context by id.
     *
     * @param int $id The integer id of the Resource.
     * @return string The URI of the Resource.
     */
    protected function getResourceUri($id)
    {
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
     * @param ErrorData|array|null $error
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

        if (zoomx()->getRoutingMode() === Service::ROUTING_STRICT || $this->modx->request->hasRoute()) {
            $this->invokeEvent($error);
            $tpl = $this->getErrorTpl($error->code, $this->modx->getOption('zoomx_default_tpl', null, 'error.tpl'));
            switch (true) {
                case $error->object instanceof Error:
                    $type = 'error';
                    break;
                case $error->object instanceof HttpException:
                    $type = 'http-exception';
                    break;
                default:
                    $type = 'exception';
            }
            parserx()->setTpl($tpl, ['e' => $error, 'type' => $type, 'showErrorDetails' => (bool)$this->modx->getOption('zoomx_show_error_details', null, $this->modx->user->get('sudo'))]);
            $this->sendError($error);
        } else {
            //MODX mode
            switch ($error->code) {
                case 401:
                case 403:
                    $this->modx->sendUnauthorizedPage();
                    break;
                case 404:
                    $this->modx->sendErrorPage();
                    break;
                default:
                    $this->modx->sendError('', [
                        'error_pagetitle' => $error->title,
                        'error_message' => $error->message,
                    ]);
            }
        }
    }

    protected function sendError(ErrorData $error)
    {
        while (ob_get_level() && @ob_end_clean()) {}
        if (!XPDO_CLI_MODE) {
            $headers = $error->object instanceof HttpException ? $error->object->getHeaders() : [500 => $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error'];
            foreach ($headers as $header => $value) {
                $header = is_int($header) ? $value : "$header: $value";
                header($header);
            }
            echo parserx()->process();
        } else {
            echo $error->title, "\n", $error->message, "\n";
        }
        exit();
    }

    protected function invokeEvent(ErrorData $error)
    {
        switch ($error->code) {
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
            'error_type' => get_class($error->object),
            'error_code' => $error->code,
            'error_pagetitle' => $error->title,
            'error_message' => $error->message,
            'e' => $error->object,
        ]);
    }

    /**
     * @param int $code
     * @param string null $default
     * @return mixed|string|null
     * @throws \ReflectionException
     * @throws \SmartyException
     */
    private function getErrorTpl($code, $default = null)
    {
        $ext = $this->modx->getOption('zoomx_template_extension', null, 'tpl');
        $tpl = $code . (!empty($ext) ? ".$ext" : '');

        return parserx()->templateExists($tpl) ? $tpl : $default;
    }
}
