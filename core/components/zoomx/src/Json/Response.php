<?php
namespace Zoomx\Json;

use modResponse;
use modX;

class Response extends modResponse
{
    /** @var array */
    public $data = [];
    /** @var array */
    protected $headers = [];
    /** @var ResponseHandler */
    protected $handler;


    /**
     * @param modX $modx A reference to the modX instance
     */
    public function __construct(modX $modx)
    {
        parent::__construct($modx);
        session_register_shutdown();
        $header = 'application/json';
        if ($charset = $modx->getOption('modx_charset', null, 'UTF-8')) {
            $header .= '; charset=' . $charset;
        }
        $this->header('Content-Type', $header);
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
     * @param string|int $header
     * @param string|null $value
     * @return string|$this
     */
    public function header($header, $value = null)
    {
        if (func_num_args() === 1) {
            return @$this->headers[$header];
        }
        $this->headers[$header] = $value;

        return $this;
    }

    /**
     * @param $header
     * @return bool
     */
    public function hasHeader($header)
    {
        return isset($this->headers[$header]);
    }

    /**
     * @param $header
     * @return $this
     */
    public function removeHeader($header)
    {
       unset($this->headers[$header]);

       return $this;
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
        $this->sendHeaders();

        while (ob_get_level() && @ob_end_clean()) {}

        echo $this->handler->prepare($this->data)->addData(['meta' => zoomx()->getRequestInfo()]);

        exit();
    }

    protected function sendHeaders()
    {
        foreach ($this->headers as $header => $value) {
            $header = is_int($header) ? $value : "{$header}: {$value}";
            header($header);
        }
    }
}