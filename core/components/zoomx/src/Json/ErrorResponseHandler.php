<?php

namespace Zoomx\Json;


class ErrorResponseHandler extends ResponseHandler
{
    /**
     * {@inheritDoc}
     */
    public function prepare($data)
    {
        $this->data = [
            'success' => false,
            'errors' => $data,
        ];

        return $this;
    }
}