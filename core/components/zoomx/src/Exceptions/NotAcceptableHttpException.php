<?php

namespace Zoomx\Exceptions;

use Throwable;

class NotAcceptableHttpException extends HttpException
{
    protected $title = 'Error 406: Not Acceptable';

    /**
     * @param string|null $message The internal exception message
     * @param Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers
     */
    public function __construct($message = null, Throwable $previous = null, array $headers = [], $code = null)
    {
        $headers[406] = $_SERVER['SERVER_PROTOCOL'] . ' 406 Not Acceptable';
        $message = $message ?? "The request is not acceptable!";

        parent::__construct(406, $message, $previous, $headers, $code);
    }
}
