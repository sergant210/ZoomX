<?php

namespace Zoomx\Contracts;


interface Arrayable
{

    /**
     * Returns a representation of the object as a native PHP array.
     *
     * @return array Associative array of object data.
     */
    public function toArray();

}