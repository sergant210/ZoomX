<?php

namespace Zoomx\Contracts\Json;

interface ResponseInterface
{
    /**
     * @param array $data
     * @return $this
     */
    public function addData(array $data);

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data = []);

    /**
     * @return array
     */
    public function getData();

    /**
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code);

    /**
     * @return int
     */
    public function getStatusCode();
}