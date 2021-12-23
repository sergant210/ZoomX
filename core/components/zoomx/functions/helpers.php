<?php

if (!function_exists('zoomx')) {
    /**
     * ZoomX helper for ZoomService.
     * @param string|array|null $key
     * @return Zoomx\Service|mixed
     */
    function zoomx($key = null)
    {
        $instance = Zoomx\Service::getInstance();

        if (is_array($key)) {
            return $instance->set($key);
        }

        return empty($key) ? $instance : $instance->get($key);
    }
}

if (!function_exists('parserx')) {
    /**
     * ZoomX helper for the current parser.
     * @return \modParser|\Zoomx\Contracts\ParserInterface
     * @throws \ReflectionException
     */
    function parserx()
    {
        return Zoomx\Service::getInstance()->getParser();
    }
}

if (! function_exists('viewx')) {
    /**
     * Get a view object for the given template.
     *
     * @param  string  $tpl
     * @param  array   $data
     * @return Zoomx\View
     */
    function viewx($tpl, $data = [])
    {
        return Zoomx\Service::getInstance()->getView($tpl, $data);
    }
}

if (! function_exists('jsonx')) {
    /**
     * Get a JsonResponse object.
     *
     * @param array $data
     * @param array $headers
     * @param int $statusCode
     * @return Zoomx\Json\Response
     */
    function jsonx(array $data = [], array $headers = [], $statusCode = 200)
    {
        $response = Zoomx\Service::getInstance()->getJsonResponse();
        if (!empty($headers)) {
            $response->headers->add($headers);
        }
        return $response->setData($data)->setStatusCode($statusCode);
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
        Zoomx\Service::getInstance()->abort($code, $message, $title, $headers);
    }
}

if (! function_exists('filex')) {
    /**
     * Creates a FileResponse object.
     *
     * @param  string  $path
     * @param  bool  $isAttachment
     * @param  bool  $deleteFileAfterSend
     * @return Zoomx\FileResponse
     *
     * @throws Zoomx\Exceptions\FileException
     */
    function filex($path, $isAttachment = false, $deleteFileAfterSend = false)
    {
        return Zoomx\Service::getInstance()->getFileResponse($path, $isAttachment, $deleteFileAfterSend);
    }
}

if (! function_exists('redirectx')) {
    /**
     * Creates a Redirect response object.
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     *
     * @return \Zoomx\RedirectResponse|\modResponse
     */
    function redirectx($url, $status = 302, array $headers = [])
    {
        return Zoomx\Service::getInstance()->getRedirectResponse($url, $status, $headers);
    }
}