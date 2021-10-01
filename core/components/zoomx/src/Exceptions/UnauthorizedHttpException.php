<?php

namespace Zoomx\Exceptions;

use Throwable;


class UnauthorizedHttpException extends HttpException
{
    protected $title = 'Error 401: Unauthorized';

    /**
     * @param string|null $message The internal exception message
     * @param Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers
     */
    public function __construct($message = null, Throwable $previous = null, array $headers = [], $code = null)
    {
        $headers[401] = ($_SERVER['SERVER_PROTOCOL'] ?? 'http') . ' 401 Unauthorized';
        $message = $message ?? "You are not authorized to view the requested content.";

        parent::__construct(401, $message, $previous, $headers, $code);
    }
}