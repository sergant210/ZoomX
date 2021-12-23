<?php

namespace Zoomx\Elements;

use modX;

abstract class Snippet
{
    /** @var modX $modx */
    protected $modx;


    /**
     * @param modX $modx
     */
    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    abstract public function process(array $properties = []);
}