<?php

namespace Zoomx\Exceptions;

use RuntimeException;
use Throwable;
use Zoomx\Contracts\Arrayable;


class FileException extends RuntimeException implements Arrayable
{
    /** @var string */
    protected $title = 'Error';
    /** @var array  */
    protected $headers;


    public function __construct(string $message = null, int $code = 0, Throwable $previous = null, array $headers = [])
    {
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
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
            'message' => $this->message,
        ];
    }
}
