<?php
namespace Zoomx\Support;

use modContentType;
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
     * @return string|null
     */
    public function detect()
    {
        $headers = headers_list();
        foreach($headers as $header) {
            if (preg_match('~content-type:\s*(\w+/\w+)~i', $header, $match)) {
                $this->mimeType = $match[1];
                break;
            }
        }
        if (empty($this->mimeType)) {
            $ext = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);
            $this->mimeType = $this->mimeTypes[$ext];
        }

        return $this->mimeType;
    }
}