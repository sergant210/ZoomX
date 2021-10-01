<?php
namespace Zoomx\Json;

use modResponse;
use modX;
use Zoomx\Support\Repository;

class Response extends modResponse
{
    /** @var array */
    protected $data = [];
    /** @var Repository */
    protected $headers;
    /** @var int */
    protected $statusCode = 200;


    /**
     * @param modX $modx A reference to the modX instance
     */
    public function __construct(modX $modx)
    {
        parent::__construct($modx);
        $contentType = 'application/json';
        if ($charset = $modx->getOption('modx_charset', null, 'UTF-8')) {
            $contentType .= '; charset=' . $charset;
        }
        $this->headers = new Repository(['Content-Type'=> $contentType]);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function addData(array $data)
    {
        $this->data = array_merge($this->data, $data);

        return $this;
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
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code)
    {
        if ($code >= 100 && $code < 600) {
            $this->statusCode = $code;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * {@inheritDoc}
     */
    public function outputContent(array $options = array())
    {
        while (ob_get_level() && @ob_end_clean()) {}
        $this->sendHeaders();
        echo $this->prepare();
        exit();
    }

    /**
     * Prepare response data to a formatted array.
     * @return string
     */
    protected function prepare(array $data = [])
    {
        $output = [
            'success' => $this->statusCode < 400,
            'data' => array_merge($this->data, $data),
        ];
        if ($this->modx->getOption('zoomx_include_request_info', null, true)) {
            $output = array_merge($output, ['meta' => zoomx()->getRequestInfo()]);
        }

        return json_encode($output);
    }

    /**
     * @return Repository
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