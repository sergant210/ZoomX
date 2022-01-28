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

    public const ROUTING_DISABLED = 0;
    public const ROUTING_SOFT     = 1;
    public const ROUTING_STRICT   = 2;

    /** @var Service */
    protected static $instance;

    protected $container;
    /** @var modX  */
    protected $modx;
    /** @var array */
    private $instances = [];
    /** @var array */
    private $exceptions = [];


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
        if ($modx->context->key !== 'mgr' && $modx->getOption('zoomx_enable_exception_handler', null, true)) {
            $exceptionHandler = $this->getExceptionHandler();
            set_exception_handler([$exceptionHandler, 'handle']);
        }

        // Register the session_write_close function
        session_register_shutdown();

        if ($modx->getOption('zoomx_enable_pdotools_adapter', null, false)) {
            $this->preparePdoToolsAdapter();
        }

        // Load modResponse class
        if (!class_exists('modResponse')) {
            require_once  MODX_CORE_PATH . 'model/modx/modresponse.class.php';
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

    public function initialize()
    {
        if ($this->modx->context->key !== 'mgr' && PHP_SAPI  !== 'cli' && (!defined('MODX_API_MODE') || !MODX_API_MODE)) {
            $this->modx->request = $this->shouldBeJson() ? $this->getJsonRequest() : $this->getRequest();
            // Load element service
            $elService = $this->getElementService();
            // Fire the event.
            $this->modx->invokeEvent('OnZoomxInit', ['zoomx' => $this]);
        }

        return $this;
    }

    /**
     * @return \Zoomx\Cache\CacheManager
     */
    public function getCacheManager()
    {
        if (!isset($this->instances['cacheManager'])) {
            $cacheManagerClass = $this->modx->getOption('zoomx_cache_manager_class', null, Cache\CacheManager::class, true);
            if (class_exists($cacheManagerClass)) {
                $this->instances['cacheManager'] = $cacheManagerClass::getInstance($this->modx);
            } else {
                throw new \InvalidArgumentException("[ZoomX] Specified cache manager class $cacheManagerClass not found.");
            }
        }

        return $this->instances['cacheManager'];
    }

    /**
     * @return ParserInterface|modParser
     * @throws \ReflectionException
     */
    public function getParser()
    {
        if (!isset($this->instances['parser'])) {
            $parserClass = $this->modx->getOption('zoomx_parser_class', null, Smarty::class, true);
            if ($parserClass === 'ZoomSmarty' || ltrim($parserClass, '\\') === Smarty::class) {
                class_exists(\Smarty::class) or require MODX_CORE_PATH . 'model/smarty/Smarty.class.php';
                if (!class_exists('ZoomSmarty')) {
                    class_alias(Smarty::class, 'ZoomSmarty');
                }
            }
            if (class_exists($parserClass) && $this->checkImplements($parserClass, ParserInterface::class)) {
                $this->instances['parser'] = new $parserClass($this->modx, $this);
            } else {
                $message = $this->modx->lexicon('zoomx_parser_implement_error');
                die($message);
            }
        }

        return $this->instances['parser'];
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
        if (!isset($this->instances['response']) || (is_string($class) && !$this->response instanceof $class)) {
            if (!class_exists('ZoomResponse')) {
                class_alias(Response::class, 'ZoomResponse');
            }
            $responseClass = $class ?? $this->modx->getOption('zoomx_response_class', null, 'ZoomResponse', true);
            $this->instances['response'] = new $responseClass($this->modx, ...$params);
        }

        return $this->instances['response'];
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
        if (!isset($this->instances['request']) || (is_string($class) && !$this->request instanceof $class)) {
            if (!class_exists('modRequest')) {
                require MODX_CORE_PATH . 'model/modx/modrequest.class.php';
            }
            if (!class_exists('ZoomRequest')) {
                class_alias(Request::class, 'ZoomRequest');
            }
            $requestClass = $class ?? $this->modx->getOption('zoomx_request_class', null, Request::class, true);
            $this->instances['request'] = new $requestClass($this->modx);
        }

        return $this->instances['request'];
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
        $class = $this->modx->getOption('zoomx_redirect_response_class', null, RedirectResponse::class);

        return $this->getResponse($class, $url, $status, $headers);
    }

    /**
     * @return ElementService
     */
    public function getElementService()
    {
        if (!isset($this->instances['elementService'])) {
            $class = $this->modx->getOption('zoomx_element_service_class', null, ElementService::class, true);
            $this->instances['elementService'] = new $class($this->modx);
        }

        return $this->instances['elementService'];
    }

    public function getRouter()
    {
        if (!isset($this->instances['router'])) {
            $class = $this->modx->getOption('zoomx_router_class', null, Routing\Router::class, true);
            $this->instances['router'] = new $class($this->modx);
        }

        return $this->instances['router'];
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
        return (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
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
     * Return Composer autoloader.
     */
    public function getLoader()
    {
        if (!$loader = include __DIR__ . '/../vendor/autoload.php') {
            $this->abort(500, 'Composer is not set.');
        }

        return $loader;
    }

    /**
     * @param string $className
     * @return bool
     * @throws \ReflectionException
     */
    protected function checkImplements($className, $interface)
    {
        $class = new \ReflectionClass($className);
        $interfaces = $class->getInterfaceNames();

        return is_array($interfaces) && in_array($interface, $interfaces);
    }

    /**
     * Get an instance.
     *
     * @param  string $name
     * @return mixed
     */
    public function get($name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $method = 'get' . ucfirst($name);

        return method_exists($this, $method) ? $this->$method() : null;
    }

    /**
     * Get an instance.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Set an instance.
     *
     * @param  string|array $name
     * @param mixed $value
     * @return $this
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->instances[$key] = $val;
            }
        } else {
            $this->instances[$name] = $value;
        }

        return $this;
    }

    /**
     * Set an instance.
     *
     * @param  string  $name
     * @param mixed $value
     * @return $this
     */
    public function __set($name, $value = null)
    {
        return $this->set($name, $value);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->instances[$name]);
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
     * @param array|int $cacheOptions Cache options or cache lifetime in seconds
     * @return mixed
     */
    public function runSnippet(string $name, array $properties = [], $cacheOptions = [])
    {
        return $this->get('elementService')->runSnippet($name, $properties, $cacheOptions);
    }

    /**
     * Executes a file like a snippet.
     * @param string $name
     * @param array $scriptProperties
     * @param array|int $cacheOptions Cache options or cache lifetime in seconds.
     * @return mixed
     */
    public function runFileSnippet(string $name, array $scriptProperties, $cacheOptions = [])
    {
        return $this->get('elementService')->runFileSnippet($name, $scriptProperties, $cacheOptions);
    }

    private function getExceptionHandler()
    {
        $exceptionHandlerClass = $this->modx->getOption('zoomx_exception_handler_class', null, ExceptionHandler::class, true);

        return new $exceptionHandlerClass($this->modx);
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

    private function preparePdoToolsAdapter(): void
    {
        $corePath = $this->modx->getOption('zoomx_core_path', null, MODX_CORE_PATH . 'components/zoomx/');
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
            $this->modx->setOption('pdotools_class_path', $corePath . 'pdotools/');
        } else {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[pdoToolsZoomx] pdoTools class is not found.');
        }
        if (class_exists('pdoFetch')) {
            $this->modx->setOption('pdoFetch.class', 'pdoFetchZoomx');
            $this->modx->setOption('pdofetch_class_path', $corePath . 'pdotools/');
        } else {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[pdoFetchZoomx] pdoFetch class is not found.');
        }
        include $corePath . 'pdotools/pdotoolsadapter.php';
    }

    private function __clone() {}

    private function __wakeup() {}
}