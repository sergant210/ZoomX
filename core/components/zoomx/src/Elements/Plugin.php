<?php

namespace Zoomx\Elements;

use modX;

abstract class Plugin
{
    /** @var modX $modx */
    protected $modx;
    /** @var bool */
    public $disabled = false;
    /** @var array */
    public static $events = [
        //'OnHandleRequest' => -100, // Priority for the event.
    ];


    /**
     * @param modX $modx
     */
    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }
}