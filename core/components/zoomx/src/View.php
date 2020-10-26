<?php

namespace Zoomx;


class View
{
    /** @var string */
    public $name;
    /** @var array */
    public $data = [];
    /** @var string */
    public $content;

    public function __construct($name, array $data = [])
    {
        $this->name = $name;
        $this->data = $data;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function hasData()
    {
        return count($this->data) > 0;
    }

    public function hasContent()
    {
        return isset($this->content);
    }

    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }
}