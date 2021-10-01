<?php

namespace Zoomx;


class View
{
    /** @var string File name */
    public $name;
    /** @var array  Template variables to assign */
    public $data = [];
    /** @var string Content */
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