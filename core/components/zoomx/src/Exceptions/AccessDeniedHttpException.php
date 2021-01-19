<?php

namespace Zoomx\Exceptions;

use Throwable;

class AccessDeniedHttpException extends HttpException
{
    protected $title = 'Error 403: Forbidden';

    /**
     * @param string|null $message The internal exception message
     * @param \Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers
     */
    public function __construct($message = null, Throwable $previous = null, array $headers = [], $code = null)
    {
        $headers[403] = $_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden';
        $message = $message ?? "Access denied!";

        parent::__construct(403, $message, $previous, $headers, $code);
    }
}
