<?php
namespace Zoomx;

use modParser;
use modX;
use modRequest;
use modResponse;
use Zoomx\Json\Response as JsonResponse;
use Zoomx\Contracts\ParserInterface;
use Zoomx\Support\ContentTypeDetector;
use Zoomx\Support\ElementService;
use Zoomx\Support\Macroable;


class Service
{
    use Macroable;

    const ROUTING_DISABLED = 0;
    const ROUTING_SOFT     = 1;
    const ROUTING_STRICT   = 2;

    /** @var Service */
    protected static $instance;
    /** @var modX  */
    protected $modx;
    /** @var ParserInterface */
    protected $parser;
    /** @var Response */
    protected $response;
    /** @var Request */
    protected $request;
    /** @var ElementService */
    protected $elementService;
    /** @var array */
    protected $exceptions = [];


    /**
     * Service constructor.
     * @param modX $modx
     */
    private function __construct(modX $modx)
    {
        $this->modx = $modx;
        $modx->zoomService = $this;

        class_alias(View::class, 'ZoomView');
        $modx->lexicon->load('zoomx:default');
        $this->loadExceptions();

        // Set exception handler
        if ($modx->getOption('zoomx_enable_exception_handler', null, true)) {
            $exceptionHandler = $this->getExceptionHandler();
            set_exception_handler([$exceptionHandler, 'handle']);
        }

        // Register the session_write_close function
        session_register_shutdown();

        if ($modx->getOption('zoomx_enable_pdotools_adapter', null, false)) {
            $this->preparePdoToolsAdapter();
        }
        // Fire the event.
        $modx->invokeEvent('onZoomxInit');
    }

    private function getExceptionHandler()
    {
        $exceptionHandlerClass = $this->modx->getOption('zoomx_exception_handler_class', null, ExceptionHandler::class, true);

        return new $exceptionHandlerClass($this->modx, $this->getRequest()->getRequestHandler());
    }

    private function loadExceptions()
    {
        $this->exceptions = require dirname(__DIR__) . '/config/exceptions.php';
        $customFile = MODX_CORE_PATH . MODX_CONFIG_KEY . '/exceptions.php';
        if (file_exists($customFile)) {
            $customExceptions = require $customFile;
        }
        if (!empty($customExceptions) && is_array($customExceptions)) {
            foreach ($customExceptions as $code => $class) {
                $this->exceptions[$code] = $class;
            }
        }
    }

    /**
     * @param modX|null $modx
     * @return Service
     */
    public static function getInstance($modx = null) {
        if (self::$instance === null && $modx) {
            self::$instance = new self($modx);
        }

        return self::$instance;
    }

    /**
     * @return ParserInterface|modParser
     * @throws \ReflectionException
     */
    public function getParser()
    {
        if (!isset($this->parser)) {
            $parserClass = $this->modx->getOption('zoomx_parser_class', null, Smarty::class, true);
            if ($parserClass === 'ZoomSmarty' || ltrim($parserClass, '\\') === Smarty::class) {
                class_exists(\Smarty::class) or require MODX_CORE_PATH . 'model/smarty/Smarty.class.php';
                if (!class_exists('ZoomSmarty')) {
                    class_alias(Smarty::class, 'ZoomSmarty');
                }
            }
            if (class_exists($parserClass) && $this->checkImplements($parserClass, ParserInterface::class)) {
                $this->parser = new $parserClass($this->modx, $this);
            } else {
                $message = $this->modx->lexicon('zoomx_parser_implement_error');
                die($message);
            }
        }

        return $this->parser;
    }

    /**
     * @param Response|modResponse $response
     * @return self
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @param string|null $class
     * @return Response|modResponse
     */
    public function getResponse($class = null, ...$params)
    {
        if (!isset($this->response) || (is_string($class) && !$this->response instanceof $class)) {
            if (!class_exists('modResponse')) {
                require  MODX_CORE_PATH . 'model/modx/modresponse.class.php';
            }
            if (!class_exists('ZoomResponse')) {
                class_alias(Response::class, 'ZoomResponse');
            }
            $responseClass = $class ?? $this->modx->getOption('zoomx_response_class', null, 'ZoomResponse', true);
            $this->response = new $responseClass($this->modx, ...$params);
        }

        return $this->response;
    }

    /**
     * @return JsonResponse|modResponse
     */
    public function getJsonResponse()
    {
        $class = $this->modx->getOption('zoomx_json_response_class', null, JsonResponse::class);

        return $this->getResponse($class);
    }

    /**
     * @param Request|modRequest $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param string $class
     * @return modRequest
     */
    public function getRequest($class = null)
    {
        if (!isset($this->request) || (is_string($class) && !$this->request instanceof $class)) {
            if (!class_exists('modRequest')) {
                require MODX_CORE_PATH . 'model/modx/modrequest.class.php';
            }
            if (!class_exists('ZoomRequest')) {
                class_alias(Request::class, 'ZoomRequest');
            }
            $requestClass = $class ?? $this->modx->getOption('zoomx_request_class', null, Request::class, true);
            $this->request = new $requestClass($this->modx);
        }

        return $this->request;
    }

    /**
     * @return Request|modRequest
     */
    public function getJsonRequest()
    {
        $class = $this->modx->getOption('zoomx_json_request_class', null, Json\Request::class);

        return $this->getRequest($class);
    }

    /**
     * Creates a FileResponse object.
     *
     * @param  string  $path
     * @param  string  $isAttachment
     * @param  string  $deleteFileAfterSend
     * @return FileResponse|modResponse
     *
     * @throws Zoomx\Exceptions\FileException
     */
    public function getFileResponse($path, $isAttachment = false, $deleteFileAfterSend = false)
    {
        $class = $this->modx->getOption('zoomx_file_response_class', null, FileResponse::class);

        return $this->getResponse($class, $path, $isAttachment, $deleteFileAfterSend);
    }

    /**
     * Creates a redirect response.
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     *
     * @return RedirectResponse|modResponse
     *
     * @throws InvalidArgumentException
     */
    public function getRedirectResponse($url, $status = 302, array $headers = [])
    {
        $class = $this->modx->getOption('zoomx_file_response_class', null, RedirectResponse::class);

        return $this->getResponse($class, $url, $status, $headers);
    }

    /**
     * @return ElementService
     */
    public function getElementService()
    {
        if (!isset($this->elementService)) {
            $class = $this->modx->getOption('zoomx_element_service_class', null, ElementService::class, true);
            $this->elementService = new $class($this->modx);
        }

        return $this->elementService;
    }

    public function getView(string $name, array $data)
    {
        $class = $this->modx->getOption('zoomx_view_class', null, View::class, true);
        $view = new $class($name, $data);

        return $view instanceof View ? $view : null;
    }

    /**
     * @return bool
     */
    public function shouldBeJson()
    {
        return (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
               (isset($_SERVER['Content-Type']) &&  strpos($_SERVER['Content-Type'], 'application/json') !== false);
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @return int
     */
    public function getRoutingMode()
    {
        return (int)$this->modx->getOption('zoomx_routing_mode', null, self::ROUTING_SOFT);
    }

    /**
     * @return array
     */
    public function getRequestInfo(): array
    {
        $totalTime = (microtime(true) - $this->modx->startTime);
        $queryTime = $this->modx->queryTime;
        $phpTime = $totalTime - $queryTime;
        $queryTime = number_format($queryTime, 4) . ' s';
        $totalTime = number_format($totalTime, 4) . ' s';
        $phpTime = number_format($phpTime, 4) . ' s';

        return  [
            'total_time' => $totalTime,
            'query_time' => $queryTime,
            'php_time' => $phpTime,
            'queries' => $this->modx->executedQueries ?? 0,
            'source' => $this->modx->resourceGenerated ? "database" : "cache",
            'memory' => number_format(memory_get_usage(true) / 1024, 0, ",", " ") . ' KB',
        ];
    }

    /**
     * @return ContentTypeDetector|null
     */
    public function getContentTypeDetector()
    {
        $class = $this->modx->getOption('zoomx_content_type_detector_class', null, ContentTypeDetector::class);
        return new $class($this->modx);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function autoloadResource(bool $value = true)
    {
        $this->modx->setOption('zoomx_autoload_resource', $value);

        return $this;
    }

    /**
     * @param string|int $identifier
     * @param array $options
     * @return \modResource|null
     */
    public function getResource($identifier, array $options = [])
    {
        return $this->getRequest()->getResource('', $identifier, $options);
    }

    /**
     * Throw an HttpException with the given data.
     *
     * @param  int     $code
     * @param  string  $message
     * @param  string  $title
     * @param  array   $headers
     *
     * @throws \Zoomx\Exceptions\HttpException
     */
    public function abort($code, $message = null, $title = null, array $headers = [])
    {
        $class = $this->getExceptionClass($code);
        $exception = is_string($class)
            ? new $class($message, null, $headers)
            : new Exceptions\HttpException($code, $message, null, $headers);
        if (isset($title)) {
            $exception->setTitle($title);
        }

        throw $exception;
    }

    /**
     * Returns an exception class for the specified code.
     * @param int $code Http code
     * @param string null $default Default exception class
     * @return string|null
     */
    public function getExceptionClass(int $code, $default = null)
    {
        return $this->exceptions[$code] ?? $default;
    }

    /**
     * @return modX
     */
    public function getModx()
    {
        return $this->modx;
    }

    /**
     * Get or set a system config setting.
     * @param string|array $key
     * @param null|mixed $default
     * @return mixed|self
     */
    public function config($key, $default = null)
    {
        if (is_string($key)) {
            return $this->modx->getOption($key, null, $default);
        }
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->modx->setOption($k, $v);
            }
        }
        return $this;
    }

    /**
     * @param string $className
     * @return false
     * @throws \ReflectionException
     */
    protected function checkImplements($className, $interface)
    {
        $class = new \ReflectionClass( $className );
        if( false === $class ) {
            return false;
        }
        $interfaces = $class->getInterfaceNames();

        return is_array($interfaces) && in_array($interface, $interfaces);
    }

    /**
     * Get a property.
     *
     * @param  string  $property
     * @return mixed
     */
    public function __get($property)
    {
        $method = 'get' . ucfirst($property);

        return method_exists($this, $method) ? $this->$method() : null;
    }

    /**
     * Replacement for modX::getChunk() method.
     * @param string $name
     * @param array $properties
     * @return string
     * @throws \SmartyException|\ReflectionException
     */
    public function getChunk(string $name, array $properties = [])
    {
        return $this->getElementService()->getChunk($name, $properties);
    }

    /**
     * Replacement for modX::snippet() method.
     * @param string $name
     * @param array $properties
     * @return mixed
     */
    public function runSnippet(string $name, array $properties = [])
    {
        return $this->getElementService()->runSnippet($name, $properties);
    }

    /**
     * Executes a file like a snippet.
     * @param string $name
     * @param array $scriptProperties
     * @return mixed
     */
    public function runFileSnippet(string $name, array $scriptProperties)
    {
        return $this->getElementService()->runFileSnippet($name, $scriptProperties);
    }

    private function preparePdoToolsAdapter(): void
    {
        if (!class_exists('pdoTools')) {
            $class = $this->modx->getOption('pdoTools.class', null, 'pdotools.pdotools', true);
            $path = $this->modx->getOption('pdotools_class_path', null, MODX_CORE_PATH . 'components/pdotools/model/', true);
            $this->modx->loadClass($class, $path, false, true);
        }
        if (!class_exists('pdoFetch')) {
            $class = $this->modx->getOption('pdoFetch.class', null, 'pdotools.pdofetch', true);
            $path = $this->modx->getOption('pdofetch_class_path', null, MODX_CORE_PATH . 'components/pdotools/model/', true);
            $this->modx->loadClass($class, $path, false, true);
        }
        if (class_exists('pdoTools')) {
            $this->modx->setOption('pdoTools.class', 'pdoToolsZoomx');
            $this->modx->setOption('pdotools_class_path', MODX_CORE_PATH . 'components/zoomx/pdotools/');
        } else {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[pdoToolsZoomx] pdoTools class is not found.');
        }
        if (class_exists('pdoFetch')) {
            $this->modx->setOption('pdoFetch.class', 'pdoFetchZoomx');
            $this->modx->setOption('pdofetch_class_path', MODX_CORE_PATH . 'components/zoomx/pdotools/');
        } else {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[pdoFetchZoomx] pdoFetch class is not found.');
        }
        include MODX_CORE_PATH . 'components/zoomx/pdotools/pdotoolsadapter.php';
    }

    private function __clone() {}

    private function __wakeup() {}
}