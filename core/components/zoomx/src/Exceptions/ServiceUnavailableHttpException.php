<?php

namespace Zoomx\Exceptions;

use Throwable;

class ServiceUnavailableHttpException extends HttpException
{
    protected $title = 'Error 503: Service Unavailable';

    /**
     * @param string|null $message The internal exception message
     * @param Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers
     */
    public function __construct($message = null, Throwable $previous = null, array $headers = [], $code = null)
    {
        $headers[503] = $_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable';
        $message = $message ?? "Site temporarily unavailable!";

        parent::__construct(503, $message, $previous, $headers, $code);
    }
}
