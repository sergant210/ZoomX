<?php

namespace Zoomx\Commands;


use modX;

abstract class Command
{
    protected $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }
}