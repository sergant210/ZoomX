<?php
namespace Zoomx\Support;

use modX;

class ContentTypeDetector
{
    protected $modx;
    /** @var string */
    protected $mimeType;
    /** @var string[] */
    protected $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'pdf' => 'application/pdf',
        'rss' => 'application/rss+xml',
        'txt' => 'text/plain',
        'xml' => 'text/xml',
    ];


    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    /**
     * @param string $default
     * @return string|null
     */
    public function detect($default = null)
    {
        if (empty($this->mimeType)) {
            $ext = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);
            $this->mimeType = $this->mimeTypes[$ext] ?? (string)$default;
        }

        return $this->mimeType;
    }
}