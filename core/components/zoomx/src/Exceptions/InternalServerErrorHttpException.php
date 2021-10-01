<?php

namespace Zoomx\Exceptions;

use Throwable;


class InternalServerErrorHttpException extends HttpException
{
    protected $title = 'Error 500: Internal Server Error';


    /**
     * @param string|null $message The internal exception message
     * @param Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers
     */
    public function __construct(string $message = null, Throwable $previous = null, array $headers = [], int $code = null)
    {
        $headers[500] = ($_SERVER['SERVER_PROTOCOL'] ?? 'http') . ' 500 Internal Server Error';
        $message = $message ?? "The server encountered an unexpected condition that prevents it from executing the request.";

        parent::__construct(500, $message, $previous, $headers, $code);
    }
}
