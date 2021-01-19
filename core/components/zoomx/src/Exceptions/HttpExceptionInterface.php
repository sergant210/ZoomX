<?php

namespace Zoomx\Exceptions;

/**
 * Interface for HTTP error exceptions.
 */
interface HttpExceptionInterface
{
    /**
     * Returns the status code.
     *
     * @return int An HTTP response status code
     */
    public function getStatusCode();

    /**
     * Returns the status code.
     *
     * @return int An HTTP response status code
     */
    public function getTitle();

    /**
     * Returns response headers.
     *
     * @return array Response headers
     */
    public function getHeaders();
}
