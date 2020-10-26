<?php
namespace Zoomx;

use modParser;
use modX;
use modRequest;
use modResponse;


class Service
{
    const ROUTES_DISABLED = 0;
    const ROUTES_SOFT     = 1;
    const ROUTES_STRICT   = 2;

    /** @var Service */
    protected static $_instance;
    /** @var modX  */
    protected $modx;
    /** @var ParserInterface */
    protected $parser;
    /** @var Response */
    protected $response;
    /** @var Request */
    protected $request;


    private function __construct(modX $modx)
    {
        $this->modx = $modx;
        $modx->zoomService = $this;

        class_alias(View::class, 'ZoomView');
    }

    public static function getInstance($modx = null) {
        if ((self::$_instance === null) && $modx) {
            self::$_instance = new self($modx);
        }

        return self::$_instance;
    }

    /**
     * @return ParserInterface|modParser
     * @throws \ReflectionException
     */
    public function getParser()
    {
        if (!isset($this->parser)) {
            $parserClass = $this->modx->getOption('zoomx_parser_class', null, Smarty::class, true);
            if (($parserClass === 'ZoomSmarty' || ltrim($parserClass, '\\') === Smarty::class) && !class_exists(\Smarty::class)) {
                require_once MODX_CORE_PATH . 'model/smarty/Smarty.class.php';
                class_alias(Smarty::class, 'ZoomSmarty');
            }
            if (class_exists($parserClass) && $this->checkImplements($parserClass, ParserInterface::class)) {
                $this->parser = new $parserClass($this->modx, $this);
            } else {
                $message = $this->modx->lexicon('Parser class must implements the "ParserInterface" interface.');
                die($message);
            }
        }

        return $this->parser;
    }

    /**
     * @param Response|modResponse $response
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return Response|modResponse
     */
    public function getResponse(): Response
    {
        if (!isset($this->response)) {
            if (!class_exists('modResponse')) {
                require  MODX_CORE_PATH . 'model/modx/modresponse.class.php';
            }
            class_alias(Response::class, 'ZoomResponse');
            $responseClass = $this->modx->getOption('zoomx_response_class', null, Response::class, true);
            $this->response = new $responseClass($this->modx, $this);
        }

        return $this->response;
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
     * @return Request|modRequest
     */
    public function getRequest()
    {
        if (!isset($this->request)) {
            if (!class_exists('modRequest')) {
                require MODX_CORE_PATH . 'model/modx/modrequest.class.php';
            }
            class_alias(Request::class, 'ZoomRequest');
            $requestClass = $this->modx->getOption('zoomx_request_class', null, Request::class, true);
            $this->request = new $requestClass($this->modx);
        }
        return $this->request;
    }

    public function getRoutesMode()
    {
        return (int)$this->modx->getOption('zoomx_routes_mode', null, self::ROUTES_SOFT);
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
     * Get a field value.
     *
     * @param  string  $field
     * @return mixed
     */
    public function __get($field)
    {
        $method = 'get' . ucfirst($field);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
    }

    private function __clone() {}

    private function __wakeup() {}
}