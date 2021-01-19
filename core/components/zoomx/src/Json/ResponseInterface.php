<?php

namespace Zoomx\Json;


interface ResponseInterface
{
    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data = []);

    /**
     * @return array
     */
    public function getData();

}