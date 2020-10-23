<?php
namespace Zoomx;

use xPDO;
use modX;
use xPDOCacheManager;
use modResource;

abstract class RequestHandler
{
    public $modx;
    /** @var modResource */
    public $resource;


    public function __construct(modX $modx)
    {
        $this->modx = $modx;
        $this->initialize();
    }

    abstract public function initialize();

    /**
     * @param int $id
     * @return modResource|null
     */
    public function getResourceFromCache($id)
    {
        $resource = null;
        $cacheKey = $this->modx->context->get('key') . "/resources/{$id}";
        $cachedResource = $this->modx->cacheManager->get($cacheKey, [
            xPDO::OPT_CACHE_KEY => $this->modx->getOption('cache_resource_key', null, 'resource'),
            xPDO::OPT_CACHE_HANDLER => $this->modx->getOption('cache_resource_handler', null, $this->modx->getOption(xPDO::OPT_CACHE_HANDLER)),
            xPDO::OPT_CACHE_FORMAT => (int)$this->modx->getOption('cache_resource_format', null, $this->modx->getOption(xPDO::OPT_CACHE_FORMAT, null, xPDOCacheManager::CACHE_PHP)),
        ]);
        if (is_array($cachedResource) && array_key_exists('resource', $cachedResource) && is_array($cachedResource['resource'])) {
            /** @var modResource $resource */
            $resource = $this->modx->newObject($cachedResource['resourceClass']);
            if ($resource) {
                $resource->fromArray($cachedResource['resource'], '', true, true, true);
                $resource->_content = $cachedResource['resource']['_content'];
//                $resource->_isForward = $isForward;
                if (isset($cachedResource['contentType'])) {
                    $contentType = $this->modx->newObject('modContentType');
                    $contentType->fromArray($cachedResource['contentType'], '', true, true, true);
                    $resource->addOne($contentType, 'ContentType');
                }
                if (isset($cachedResource['resourceGroups'])) {
                    $rGroups = array();
                    foreach ($cachedResource['resourceGroups'] as $rGroupKey => $rGroup) {
                        $rGroups[$rGroupKey]= $this->modx->newObject('modResourceGroupResource', $rGroup);
                    }
                    $resource->addMany($rGroups);
                }
                if (isset($cachedResource['policyCache'])) {
                    $resource->setPolicies([$this->modx->context->get('key') => $cachedResource['policyCache']]);
                }
                if (isset($cachedResource['elementCache'])) {
                    $this->modx->elementCache = $cachedResource['elementCache'];
                }
                if (isset($cachedResource['sourceCache'])) {
                    $this->modx->sourceCache = $cachedResource['sourceCache'];
                }
                if ($resource->get('_jscripts')) {
                    $this->modx->jscripts = array_merge($this->modx->jscripts, $resource->get('_jscripts'));
                }
                if ($resource->get('_sjscripts')) {
                    $this->modx->sjscripts = array_merge($this->modx->sjscripts, $resource->get('_sjscripts'));
                }
                if ($resource->get('_loadedjscripts')) {
                    $this->modx->loadedjscripts = array_merge($this->modx->loadedjscripts, $resource->get('_loadedjscripts'));
                }
//                $isForward = $resource->_isForward;
                $resource->setProcessed(true);
            }
        }

        return $resource;
    }

    protected function isWrongResourceContext($resource, $options)
    {
        $isForward = !empty($options['forward']);
        $differentContexts = $resource->get('context_key') !== $this->modx->context->get('key');
        $forwardNotAllowed = !$isForward || ($isForward && !$this->modx->getOption('allow_forward_across_contexts', $options, false));
        $ctxResourceNumber = $this->modx->getCount('modContextResource', ['context_key' => $this->modx->context->get('key'), 'resource' => $resource->id]);

        return $differentContexts && $forwardNotAllowed && !$ctxResourceNumber;
    }

    /**
     * Gets a requested resource and all required data.
     *
     * @param integer $identifier The identifier with which to search.
     * @param array $options An array of options for the resource fetching
     * @return modResource|null The requested modResource instance or request is forwarded to the error page, or unauthorized page.
     */
    public function getResource($identifier, array $options = []) {
        $resourceId = (int)$identifier;

        if (empty($resourceId)) {
            return null;
        }

        if (($resource = $this->getResourceFromCache($resourceId)) && !$resource->get('deleted')) {
            if ($resource->checkPolicy('load') && ($resource->get('published') || ($this->modx->getSessionState() === modX::SESSION_STATE_INITIALIZED && $this->modx->hasPermission('view_unpublished')))) {
                if ($this->isWrongResourceContext($resource, $options)) {
                    return null;
                }
                if (!$resource->checkPolicy('view')) {
                    $this->modx->sendUnauthorizedPage();
                }
            } else {
                return null;
            }
            $this->modx->invokeEvent('OnLoadWebPageCache', array(
                'resource'  => $resource,
            ));
        } else {
            if ($this->resource && $this->resource instanceof modResource) {
                $resource = $this->resource;
            } else {
                $criteria = $this->modx->newQuery('modResource');
                $criteria->select(array($this->modx->escape('modResource') . '.*'));
                $criteria->where(array('id' => $resourceId, 'deleted' => '0'));
                if ($this->modx->getSessionState() !== modX::SESSION_STATE_INITIALIZED || !$this->modx->hasPermission('view_unpublished')) {
                    $criteria->where(array('published' => 1));
                }
                $resource = $this->modx->getObject('modResource', $criteria);
            }
            if ($resource) {
                if ($this->isWrongResourceContext($resource, $options)) {
                    return null;
                }
                if (!$resource->checkPolicy('view')) {
                    $this->modx->sendUnauthorizedPage();
                }
                if ($tvs = $resource->getMany('TemplateVars', 'all')) {
                    /** @var \modTemplateVar $tv */
                    foreach ($tvs as $tv) {
                        $resource->set($tv->get('name'), array(
                            $tv->get('name'),
                            $tv->getValue($resource->get('id')),
                            $tv->get('display'),
                            $tv->get('display_params'),
                            $tv->get('type'),
                        ));
                    }
                }
                $this->modx->resourceGenerated = true;
            }
        }
        if ($resource) {
            $resource->_isForward = !empty($options['forward']);
        }

        return $resource;
    }
}