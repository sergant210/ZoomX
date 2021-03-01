<?php

return [
    400 => Zoomx\Exceptions\BadRequestHttpException::class,
    401 => Zoomx\Exceptions\UnauthorizedHttpException::class,
    403 => Zoomx\Exceptions\AccessDeniedHttpException::class,
    404 => Zoomx\Exceptions\NotFoundHttpException::class,
    405 => Zoomx\Exceptions\MethodNotAllowedHttpException::class,
    406 => Zoomx\Exceptions\NotAcceptableHttpException::class,
    415 => Zoomx\Exceptions\UnsupportedMediaTypeHttpException::class,
    500 => Zoomx\Exceptions\InternalServerErrorHttpException::class,
    503 => Zoomx\Exceptions\ServiceUnavailableHttpException::class,
];
