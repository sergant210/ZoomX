<?php

namespace Zoomx\Controllers;


use modX;

abstract class Controller
{
    protected $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }
}