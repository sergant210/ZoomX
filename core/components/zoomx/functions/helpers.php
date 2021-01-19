<?php

if (!function_exists('zoomx')) {
    /**
     * ZoomX helper for ZoomService.
     * @param string|null $property
     * @return Zoomx\Service|mixed
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

if (! function_exists('jsonx')) {
    /**
     * Get a JsonResponse object.
     *
     * @param array $data
     * @param array $headers
     * @return Zoomx\Json\Response
     */
    function jsonx(array $data = [], array $headers = [])
    {
        $response = zoomx()->getJsonResponse();
        if (!empty($headers)) {
            $response->headers->add($headers);
        }
        return $response->setData($data);
    }
}
if (! function_exists('abortx')) {
    /**
     * Throw an HttpException with the given data.
     *
     * @param  int     $code
     * @param  string  $message
     * @param  string  $title
     * @param  array   $headers
     * @return void
     *
     * @throws \Zoomx\Exceptions\HttpException
     * @throws \Zoomx\Exceptions\NotFoundHttpException
     */
    function abortx($code, $message = null, $title = null, array $headers = [])
    {
        zoomx()->abort($code, $message, $title, $headers);
    }
}