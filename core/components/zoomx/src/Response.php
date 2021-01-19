<?php
namespace Zoomx;

use Exception;
use modResponse;
use modX;

class Response extends modResponse
{
    /**
     * Prepare the response.
     */
    public function prepare()
    {
        if (!is_object($this->modx->resource) && !($this->modx->resource = $this->modx->request->getResource('', $this->modx->resourceIdentifier))) {
            $this->modx->sendErrorPage();
        }
        $this->modx->invokeEvent("OnLoadWebDocument");
    }


    /**
     * {@inheritDoc}
     */
    public function outputContent(array $options = array())
    {
        $this->prepare();

        if (!($this->contentType = $this->modx->resource->getOne('ContentType'))) {
            if ($this->modx->getDebug() === true) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, "No valid content type for RESOURCE: " . print_r($this->modx->resource->toArray(), true));
            }
            $this->modx->log(modX::LOG_LEVEL_FATAL, "The requested resource has no valid content type specified.");
        }

        if (!$this->contentType->get('binary')) {
            $zoomService = zoomx();
            if ($zoomService->getRequest()->hasRoute()) {
                try {
                    $this->modx->resource->_output = $zoomService->getParser()->process($this->modx->resource);
                } catch (Exception $e) {
                    $this->modx->log(MODX::LOG_LEVEL_ERROR, $e->getMessage());
                    $this->modx->resource->_output = str_replace(MODX_BASE_PATH, '.../', $e->getMessage());
                }
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

            /* invoke OnWebPagePrerender event */
            if (!isset($options['noEvent']) || empty($options['noEvent'])) {
                $this->modx->invokeEvent('OnWebPagePrerender');
            }

            $totalTime= (microtime(true) - $this->modx->startTime);
            $queryTime= $this->modx->queryTime;
            $queries= isset ($this->modx->executedQueries) ? $this->modx->executedQueries : 0;
            $phpTime= $totalTime - $queryTime;
            $queryTime= sprintf("%2.4f s", $queryTime);
            $totalTime= sprintf("%2.4f s", $totalTime);
            $phpTime= sprintf("%2.4f s", $phpTime);
            $source= $this->modx->resourceGenerated ? "database" : "cache";
            $memory = number_format(memory_get_usage(true) / 1024, 0,","," ") . ' kb';
            $this->modx->resource->_output= str_replace("[^q^]", $queries, $this->modx->resource->_output);
            $this->modx->resource->_output= str_replace("[^qt^]", $queryTime, $this->modx->resource->_output);
            $this->modx->resource->_output= str_replace("[^p^]", $phpTime, $this->modx->resource->_output);
            $this->modx->resource->_output= str_replace("[^t^]", $totalTime, $this->modx->resource->_output);
            $this->modx->resource->_output= str_replace("[^s^]", $source, $this->modx->resource->_output);
            $this->modx->resource->_output= str_replace("[^m^]", $memory, $this->modx->resource->_output);
        } else {
            $this->modx->beforeRender();

            /* invoke OnWebPagePrerender event */
            if (!isset($options['noEvent']) || empty($options['noEvent'])) {
                $this->modx->invokeEvent("OnWebPagePrerender");
            }
        }

        /* send out content-type, content-disposition, and custom headers from the content type */
        if ($this->modx->getOption('set_header')) {
            $type= $this->contentType->get('mime_type') ? $this->contentType->get('mime_type') : 'text/html';
            $header= 'Content-Type: ' . $type;
            if (!$this->contentType->get('binary')) {
                $charset= $this->modx->getOption('modx_charset',null,'UTF-8');
                $header .= '; charset=' . $charset;
            }
            header($header);
            if (!$this->checkPreview()) {
                $dispositionSet= false;
                if ($customHeaders= $this->contentType->get('headers')) {
                    foreach ($customHeaders as $headerKey => $headerString) {
                        header($headerString);
                        if (strpos($headerString, 'Content-Disposition:') !== false) $dispositionSet= true;
                    }
                }
                if (!$dispositionSet && $this->modx->resource->get('content_dispo')) {
                    if ($alias= $this->modx->resource->get('uri')) {
                        $name= basename($alias);
                    } elseif ($this->modx->resource->get('alias')) {
                        $name= $this->modx->resource->get('alias');
                        if ($ext= $this->contentType->getExtension()) {
                            $name .= "{$ext}";
                        }
                    } elseif ($name= $this->modx->resource->get('pagetitle')) {
                        $name= $this->modx->resource->cleanAlias($name);
                        if ($ext= $this->contentType->getExtension()) {
                            $name .= "{$ext}";
                        }
                    } else {
                        $name= 'download';
                        if ($ext= $this->contentType->getExtension()) {
                            $name .= "{$ext}";
                        }
                    }
                    $header= 'Cache-Control: public';
                    header($header);
                    $header= 'Content-Disposition: attachment; filename=' . $name;
                    header($header);
                    $header= 'Vary: User-Agent';
                    header($header);
                }
            }
        }

        /* tell PHP to call _postProcess after returning the response (for caching) */
        register_shutdown_function(array (
            & $this->modx,
            "_postProcess"
        ));

        if ($this->modx->resource instanceof modStaticResource && $this->contentType->get('binary')) {
            $this->modx->resource->process();
        } else {
            if ($this->contentType->get('binary')) {
                $this->modx->resource->_output = $this->modx->resource->process();
            }
            @session_write_close();
            echo $this->modx->resource->_output;
            while (ob_get_level() && @ob_end_flush()) {}
            flush();
            exit();
        }
    }
}