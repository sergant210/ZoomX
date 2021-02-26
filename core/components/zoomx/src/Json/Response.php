<?php
namespace Zoomx\Json;

use modResponse;
use modX;
use Zoomx\Repository;

class Response extends modResponse implements ResponseInterface
{
    /** @var array */
    protected $data = [];
    /** @var Repository */
    protected $headers;
    /** @var ResponseHandler */
    protected $handler;


    /**
     * @param modX $modx A reference to the modX instance
     */
    public function __construct(modX $modx)
    {
        parent::__construct($modx);
        session_register_shutdown();
        $contentType = 'application/json';
        if ($charset = $modx->getOption('modx_charset', null, 'UTF-8')) {
            $contentType .= '; charset=' . $charset;
        }
        $this->headers = new Repository(['Content-Type'=> $contentType]);
        $this->handler = $this->getHandler();
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data = [])
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return ResponseHandler
     */
    public function getHandler()
    {
        if ($this->handler === null || !$this->handler instanceof SuccessResponseHandler) {
            $class = $this->modx->getOption('zoomx_response_handler_class', null, SuccessResponseHandler::class, true);
            $this->handler = new $class;
        }

        return $this->handler;
    }

    /**
     * @return ResponseHandler
     */
    public function getErrorHandler()
    {
        if ($this->handler === null || !$this->handler instanceof ErrorResponseHandler) {
            $class = $this->modx->getOption('zoomx_error_response_handler_class', null, ErrorResponseHandler::class, true);
            $this->handler = new $class;
        }

        return $this->handler;
    }

    /**
     * {@inheritDoc}
     */
    public function outputContent(array $options = array())
    {
        while (ob_get_level() && @ob_end_clean()) {}
        $this->sendHeaders();
        echo $this->handler->prepare($this->data)->addData(['meta' => zoomx()->getRequestInfo()]);

        exit();
    }

    /**
     * @return \Zoomx\Repository
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    protected function sendHeaders()
    {
        foreach ($this->headers->all() as $header => $value) {
            $header = is_int($header) ? $value : "{$header}: {$value}";
            header($header);
        }
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
}