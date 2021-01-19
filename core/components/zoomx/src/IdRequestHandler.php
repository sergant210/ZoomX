<?php
namespace Zoomx;

use modX;
use modResource;

class IdRequestHandler extends RequestHandler
{
    public function initialize()
    {
        $this->modx->resourceMethod = "id";
    }

    public function getResourceIdentifier()
    {
        $key = $this->modx->getOption('request_param_id', null, 'id');

        return (int)($_REQUEST[$key] ?? 0);
    }
}