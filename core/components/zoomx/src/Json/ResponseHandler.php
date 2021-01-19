<?php

namespace Zoomx\Json;

abstract class ResponseHandler
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * Prepare response data as a formatted array.
     * @param array $data
     * @return $this
     */
    public function prepare($data)
    {
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function addData(array $data)
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $string = json_encode($this->data);

        return is_string($string) ? $string : '';
    }
}