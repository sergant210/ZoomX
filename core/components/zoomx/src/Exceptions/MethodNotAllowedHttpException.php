<?php

namespace Zoomx\Exceptions;

use Throwable;

class MethodNotAllowedHttpException extends HttpException
{
    protected $title = 'Error 405: Method Not Allowed';

    /**
     * @param array $allow An array of allowed methods
     * @param string|null $message The internal exception message
     * @param Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers Headers
     */
    public function __construct(array $allow, string $message = null, Throwable $previous = null, array $headers = [], $code = null)
    {
        $headers[405] = $_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed';
        $headers['Allow'] = strtoupper(implode(', ', $allow));
        $message = $message ?? "Method {$this->getMethod()} is not allowed. Use one of these [{$headers['Allow']}].";

        parent::__construct(405, $message, $previous, $headers, $code);
    }

    private function getMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
}