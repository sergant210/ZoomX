<?php
namespace Zoomx\Json;

use modResponse;
use modX;
use Zoomx\Contracts\Json\ResponseInterface;
use Zoomx\Support\Repository;

class Response extends modResponse implements ResponseInterface
{
    /** @var array */
    protected $data = [];
    /** @var Repository */
    protected $headers;
    /** @var int */
    protected $statusCode = 200;
    /** @var string  */
    protected $version = '1.1';

    /**
     * Status codes translation table.
     * @var array
     */
    protected $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Too Early',                                                   // RFC-ietf-httpbis-replay-04
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',                                     // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];


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
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusTexts[$this->statusCode]));
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