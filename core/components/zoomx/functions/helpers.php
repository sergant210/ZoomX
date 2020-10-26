<?php

if (!function_exists('zoomx')) {
    /**
     * ZoomX helper for ZoomService.
     * @param string|null $property
     * @return mixed
     */
    function zoomx($property = null)
    {
        global $modx;

        $instance = Zoomx\Service::getInstance($modx);

        return empty($property) ? $instance : $instance->{$property};
    }
}

if (!function_exists('parserx')) {
    /**
     * ZoomX helper for the current parser.
     * @return mixed
     */
    function parserx()
    {
        global $modx;

        return Zoomx\Service::getInstance($modx)->getParser();
    }
}

if (! function_exists('viewx')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string  $tpl
     * @param  array   $data
     * @return Zoomx\View
     */
    function viewx($tpl, $data = [])
    {
        return new Zoomx\View($tpl, $data);
    }
}