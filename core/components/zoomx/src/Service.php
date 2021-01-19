<?php
namespace Zoomx;

use modParser;
use modX;
use modRequest;
use modResponse;
use Zoomx\Json\Response as JsonResponse;
use Zoomx\Contracts\ParserInterface;


class Service
{
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
                class_alias(Smarty::class, 'ZoomSmarty');
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
     * @param string $class
     * @return Response|modResponse
     */
    public function getResponse($class = null)
    {
        if (!isset($this->response) || (is_string($class) && !$this->response instanceof $class)) {
            if (!class_exists('modResponse')) {
                require  MODX_CORE_PATH . 'model/modx/modresponse.class.php';
            }
            class_alias(Response::class, 'ZoomResponse');
            $responseClass = $class ?? $this->modx->getOption('zoomx_response_class', null, Response::class, true);
            $this->response = new $responseClass($this->modx);
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
        if (!isset($this->request) || (is_string($class) && !$this->response instanceof $class)) {
            if (!class_exists('modRequest')) {
                require MODX_CORE_PATH . 'model/modx/modrequest.class.php';
            }
            class_alias(Request::class, 'ZoomRequest');
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
        $queries = $this->modx->executedQueries ?? 0;
        $phpTime = $totalTime - $queryTime;
        $queryTime = number_format($queryTime, 4) . ' s';
        $totalTime = number_format($totalTime, 4) . ' s';
        $phpTime = number_format($phpTime, 4) . ' s';
        $memory = number_format(memory_get_usage(true) / 1024, 0, ",", " ") . ' kb';

        return  [
            'total_time' => $totalTime,
            'query_time' => $queryTime,
            'php_time' => $phpTime,
            'queries' => $queries,
            'memory' => $memory,
        ];
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
     * @throws \Zoomx\Exceptions\BadRequestHttpException
     * @throws \Zoomx\Exceptions\UnauthorizedHttpException
     * @throws \Zoomx\Exceptions\AccessDeniedHttpException
     * @throws \Zoomx\Exceptions\NotFoundHttpException
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

    private function __clone() {}

    private function __wakeup() {}
}