<?php
namespace Zoomx;

use modResponse;
use modStaticResource;
use modX;

class Response extends modResponse
{
    /**
     * {@inheritDoc}
     */
    public function outputContent(array $options = array())
    {
        $this->modx->resource = $this->modx->resource ?? $this->modx->newObject('modDocument', ['content_type' => 0]);
        if ($this->modx->resource->content_type === 0) {
            $this->contentType = $this->getContentType();
        }
        if ($this->contentType === null && !($this->contentType = $this->modx->resource->getOne('ContentType'))) {
            if ($this->modx->getDebug() === true) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, "No valid content type for the resource: " . print_r($this->modx->resource->toArray(), true));
            }
            $this->modx->log(modX::LOG_LEVEL_FATAL, "The requested resource has no valid content type specified.");
            abortx(500, 'The requested resource has no valid content type specified.');
        }

        if (!$this->contentType->get('binary')) {
            $zervice = zoomx();
            $parser = $zervice->getParser();
            if ($zervice->getRequest()->hasRoute()) {
                // File template
                $this->modx->resource->_output = $parser->process($this->modx->resource);
            } elseif ($zervice->config('zoomx_use_zoomx_parser_as_default', false)) {
                // DB template
//                $this->getTemplateContent();
//                $this->modx->resource->_output = !empty($this->modx->resource->_content)
//                    ? $parser->parse($this->modx->resource->_content)
//                    : $parser->parse($this->modx->resource->getContent());
//                $this->modx->resource->setProcessed(true);
                $this->modx->resource->_output = $parser->processResource($this->modx->resource);
            } else {
                $this->modx->resource->prepare();
            }

            if (strpos($this->contentType->get('mime_type'), 'text/html') !== false) {
                $this->modx->invokeEvent('OnBeforeRegisterClientScripts');
                /* Insert Startup jscripts & CSS scripts into template - template must have a </head> tag */
                if (($js = $this->modx->getRegisteredClientStartupScripts()) && (strpos($this->modx->resource->_output, '</head>') !== false)) {
                    /* change to just before closing </head> */
                    $this->modx->resource->_output = preg_replace("/(<\/head>)/i", $js . "\n\\1", $this->modx->resource->_output, 1);
                }

                /* Insert jscripts & html block into template  */
                if ($js = $this->modx->getRegisteredClientScripts()) {
                    if (strpos($this->modx->resource->_output, '</body>') !== false) {
                        $this->modx->resource->_output = preg_replace("/(<\/body>)/i", $js . "\n\\1", $this->modx->resource->_output, 1);
                    } else {
                        $this->modx->resource->_output .= "\n{$js}";
                    }
                }
            }
            $this->modx->beforeRender();
            $this->renderInfo($zervice->getRequestInfo());
        } else {
            $this->modx->beforeRender();
        }

        /* invoke OnWebPagePrerender event */
        if (empty($options['noEvent'])) {
            $this->modx->invokeEvent('OnWebPagePrerender');
        }
        /* send out content-type, content-disposition, and custom headers from the content type */
        if ($this->modx->getOption('set_header')) {
            $type = $this->contentType->get('mime_type') ?: 'text/html';
            $header = 'Content-Type: ' . $type;
            if (!$this->contentType->get('binary')) {
                $charset = $this->modx->getOption('modx_charset', null, 'UTF-8');
                $header .= '; charset=' . $charset;
            }
            header($header);
            if (!$this->checkPreview()) {
                $dispositionSet = false;
                if ($customHeaders = $this->contentType->get('headers')) {
                    foreach ($customHeaders as $headerKey => $headerString) {
                        header($headerString);
                        if (strpos($headerString, 'Content-Disposition:') !== false) {
                            $dispositionSet = true;
                        }
                    }
                }
                if (!$dispositionSet && $this->modx->resource->get('content_dispo')) {
                    if ($alias = $this->modx->resource->get('uri')) {
                        $name = basename($alias);
                    } elseif ($this->modx->resource->get('alias')) {
                        $name = $this->modx->resource->get('alias');
                        if ($ext = $this->contentType->getExtension()) {
                            $name .= "{$ext}";
                        }
                    } elseif ($name = $this->modx->resource->get('pagetitle')) {
                        $name = $this->modx->resource->cleanAlias($name);
                        if ($ext = $this->contentType->getExtension()) {
                            $name .= "{$ext}";
                        }
                    } else {
                        $name = 'download';
                        if ($ext = $this->contentType->getExtension()) {
                            $name .= "{$ext}";
                        }
                    }
                    $headers = [
                        'Cache-Control: public',
                        'Content-Disposition: attachment; filename=' . $name,
                        'Vary: User-Agent',
                    ];
                    foreach ($headers as $header) {
                        header($header);
                    }
                }
            }
        }

        /* tell PHP to call _postProcess after returning the response (for caching) */
        register_shutdown_function([$this->modx,"_postProcess"]);

        if ($this->modx->resource instanceof modStaticResource && $this->contentType->get('binary')) {
            $this->modx->resource->process();
        } else {
            if ($this->contentType->get('binary')) {
                $this->modx->resource->_output = $this->modx->resource->process();
            }
//            @session_write_close();
            echo $this->modx->resource->_output;
            while (ob_get_level() && @ob_end_flush()) {}
            flush();
            exit();
        }
    }

    protected function getTemplateContent()
    {
        $resource = $this->modx->resource;
        if (!$resource->_processed) {
            $resource->_output = '';
            if (empty($resource->_content)) {
                /** @var \modTemplate $baseElement */
                if ($resource->get('template') && $baseElement = $resource->getOne('Template')) {
                    $resource->_content = $baseElement->getContent();
                }
            }
        }
    }

    private function renderInfo($info)
    {
        $this->modx->resource->_output = str_replace("[^q^]", $info['queries'], $this->modx->resource->_output);
        $this->modx->resource->_output = str_replace("[^qt^]", $info['query_time'], $this->modx->resource->_output);
        $this->modx->resource->_output = str_replace("[^p^]", $info['php_time'], $this->modx->resource->_output);
        $this->modx->resource->_output = str_replace("[^t^]", $info['total_time'], $this->modx->resource->_output);
        $this->modx->resource->_output = str_replace("[^s^]", $info['source'], $this->modx->resource->_output);
        $this->modx->resource->_output = str_replace("[^m^]", $info['memory'], $this->modx->resource->_output);
    }

    /**
     * @return \modContentType|null
     */
    protected function getContentType()
    {
        $mimeType = $this->checkHeaderList();
        if (empty($mimeType) && $this->modx->getOption('zoomx_autodetect_content_type', null, true)) {
            $mimeType = zoomx()->getContentTypeDetector()->detect('text/html');
        }

        return $this->modx->getObject('modContentType', ['mime_type' => $mimeType]);
    }

    private function checkHeaderList()
    {
        $mimeType = null;
        $headers = headers_list();
        foreach($headers as $header) {
            if (preg_match('~content-type:\s*(\w+/\w+)~i', $header, $match)) {
                $mimeType = $match[1];
                break;
            }
        }
        return $mimeType;
    }
}