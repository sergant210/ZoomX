<?php

namespace Zoomx;

use Error;
use modX;
use Throwable;
use Zoomx\DTO\Error as ErrorData;
use Zoomx\Exceptions\HttpException;

class ExceptionHandler
{
    protected $modx;

    public function __construct(modX $modx) {
        $this->modx = $modx;
    }

    public function handle(Throwable $e) {
        $code = $e->getCode() === 0 ? 500 : $e->getCode();
        $basePath = str_replace('/', DIRECTORY_SEPARATOR, $this->modx->getOption('base_path', null, MODX_BASE_PATH));
        $trace = array_reverse($e->getTrace());
        $data = [
            'code' => $code,
            'message' => $this->filterPath($e->getMessage(), $basePath),
            'file' => $this->filterPath($e->getFile(), $basePath),
            'line' => $e->getLine(),
            'trace' => $this->filterPath($trace, $basePath),
            'title' => $e instanceof HttpException ? $e->getTitle() : "Error $code: Internal Server Error",
            'object' => $e,
        ];
        if ($e instanceof Error || $code >= 500) {
            $errorType = get_class($e);
            $this->modx->log(MODX_LOG_LEVEL_ERROR, "[$errorType] " . $e->getMessage(), '', '', $e->getFile(), $e->getLine());
        }

        $error = new ErrorData($data);
        $this->getRequestHandler()->sendErrorPage($error);
    }

    private function filterPath($data, $basePath)
    {
        if (is_string($data)) {
            return str_replace($basePath, '...' . DIRECTORY_SEPARATOR, $data);
        }
        if (is_array($data)) {
            foreach ($data as &$line) {
                $line['file'] = str_replace($basePath, '...' . DIRECTORY_SEPARATOR, $line['file']);
            }
        }
        return $data;
    }

    protected function getRequestHandler()
    {
        return \zoomx('request')->getRequestHandler();
    }
}