<?php

namespace Zoomx\Exceptions;

use Throwable;


class NotFoundHttpException extends HttpException
{
    protected $title = 'Error 404: Page not found';

    /**
     * Constructor.
     *
     * @param string $message The internal exception message
     * @param Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers Headers
     */
    public function __construct($message = null, Throwable $previous = null, array $headers = [], $code = null)
    {
        $headers[404] = $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found';
        $message = $message ?? "The page you requested was not found.";

        parent::__construct(404, $message, $previous, $headers, $code);
    }
}
