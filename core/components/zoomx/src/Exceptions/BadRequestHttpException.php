<?php

namespace Zoomx\Exceptions;

use Throwable;


class BadRequestHttpException extends HttpException
{
    protected $title = 'Error 400: Bad Request';

    /**
     * Constructor.
     *
     * @param string $message The internal exception message
     * @param Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers
     */
    public function __construct($message = null, Throwable $previous = null, array $headers = [], $code = null)
    {
        $headers[400] = $_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request';
        $message = $message ?? "Check your request or cookies.";

        parent::__construct(400, $message, $previous, $headers, $code);
    }
}
