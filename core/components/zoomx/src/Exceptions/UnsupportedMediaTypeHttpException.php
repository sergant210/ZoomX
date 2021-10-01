<?php

namespace Zoomx\Exceptions;

use Throwable;


class UnsupportedMediaTypeHttpException extends HttpException
{
    protected $title = 'Error 415: Unsupported Media Type';


    /**
     * @param string|null $message The internal exception message
     * @param Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers
     */
    public function __construct(string $message = null, Throwable $previous = null, array $headers = [], int $code = null)
    {
        $headers[415] = ($_SERVER['SERVER_PROTOCOL'] ?? 'http') . ' 415 Unsupported Media Type';
        $message = $message ?? "Make sure you send the correct request type.";

        parent::__construct(415, $message, $previous, $headers, $code);
    }
}
