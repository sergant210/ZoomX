<?php

namespace Zoomx\Json;


class SuccessResponseHandler extends ResponseHandler
{
    /**
     * {@inheritDoc}
     */
    public function prepare($data)
    {
        $this->data = [
            'success' => true,
            'data' => $data,
        ];

        return $this;
    }
}