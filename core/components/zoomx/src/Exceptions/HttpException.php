<?php

namespace Zoomx\Exceptions;

use RuntimeException;
use Throwable;
use Zoomx\Contracts\Arrayable;


class HttpException extends RuntimeException implements HttpExceptionInterface, Arrayable
{
    protected $statusCode;
    protected $title;
    protected $headers;

    public function __construct(int $statusCode, string $message = null, Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        //$code = $code ?? $statusCode;

        parent::__construct($message, $code, $previous);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return $this
     */
    public function setTitle(string $title = null)
    {
        $this->title = $title ?? $this->title;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set response headers.
     *
     * @param array $headers Response headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return [
            'code' => $this->code,
            'status' => $this->statusCode,
            'title' => $this->title,
            'detail' => $this->message,
        ];
    }
}
