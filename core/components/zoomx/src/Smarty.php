<?php
namespace Zoomx;

use modResource;
use Smarty as BaseSmarty;
use modX;
use modElement;
use SmartyException;

class Smarty extends BaseSmarty implements ParserInterface {
    /** @var modX $modx A reference to the modX instance */
    protected $modx;
    /** @var Service  */
    protected $zoomService;
    /** @var Repository  */
    protected $chunkRepository;
    /** @var Repository  */
    protected $snippetRepository;
    /** @var View */
    protected $tpl;

    /**
     * @param modX $modx A reference to the modX object
     * @param Service $zoomService
     */
    public function __construct(modX $modx, Service $zoomService)
    {
        parent::__construct();

        $this->modx = $modx;
        $this->zoomService = $zoomService;
        $this->chunkRepository = new Repository();
        $this->snippetRepository = new Repository();

        $corePath = $modx->getOption('zoomx_core_path', null, MODX_CORE_PATH . 'components/zoomx/');
        $cachePath = $modx->getCachePath();
        $theme = str_replace(['.', '/'], '', trim($modx->getOption('zoomx_theme', null, 'default')));
        $this->template_dir = $modx->getOption('zoomx_template_dir', null, $corePath . 'templates/') . ($theme ? $theme . '/' : '');
        $this->cache_dir = $cachePath . ltrim($modx->getOption('zoomx_smarty_cache_dir', null, 'zoomx/smarty/cache/'), '/');
        $this->compile_dir = $modx->getOption('zoomx_smarty_compile_dir', null, $cachePath . 'zoomx/smarty/compiled/');
        $this->setConfigDir($modx->getOption('zoomx_smarty_config_dir', null, $corePath . 'config/'));

        $this->caching = $modx->getOption('cache_resource', null, true)
            ? $modx->getOption('zoomx_caching', null, Smarty::CACHING_LIFETIME_CURRENT)
            : Smarty::CACHING_OFF;
        $this->cache_lifetime = (int)$modx->getOption('cache_resource_expires', null, 0);
        $this->cache_lifetime = $this->cache_lifetime > 0 ? $this->cache_lifetime : -1;

        $pluginsDir = [
            $corePath . 'smarty_plugins/',
        ];

        $customPluginDir = $modx->getOption('zoomx_smarty_custom_plugin_dir', null, '');
        if (!empty($customPluginDir)) {
            $pluginsDir[] = $customPluginDir;
        }
        $this->addPluginsDir($pluginsDir);

        $preFilters = ['scripts', 'ignore'];
        foreach ($preFilters as $filter) {
            $this->loadFilter('pre', $filter);
        }

        $this->assign('modx', $modx, true);
    }

    /**
     * {@inheritDoc}
     */
    public function process(modResource $resource = null): string
    {
        $output = '';

        if ($this->hasTpl()) {
            if ($this->templateExists($this->tpl->name)) {
                $this->setCaching($this->caching && ($resource->cacheable ?? true));
                $cacheId = $resource ? "doc_" . $resource->id : null;

                try {
                    if ($this->tpl->hasData()) {
                        $this->assign($this->tpl->data);
                    }
                    $output = $this->fetch($this->tpl->name, $cacheId);
                    $output = $output === false ? '' : $output;
                } catch (SmartyException $e) {
                    $this->modx->log(MODX::LOG_LEVEL_ERROR, $e->getMessage());
                    $output = str_replace(MODX_BASE_PATH, '.../', $e->getMessage());
                }
            } elseif ($this->tpl->hasContent()) {
                $output = $this->tpl->content;
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, "Specified template \"{$this->tpl}\" doesn't exist");
            }
            if ($resource) {
                $resource->setProcessed(true);
            }
        }

        return $output;
    }

    /**
     * {@inheritDoc}
     */
    public function parse($string, array $properties = [])
    {
        if (empty($string)) {
            return '';
        }
        $tmpl = $this->createTemplate('string:' . $string, $this);
        if (!empty($properties)) {
            $tmpl->assign($properties);
        }
        $tmpl->caching = Smarty::CACHING_OFF;

        return $tmpl->fetch();
    }

    /**
     * {@inheritDoc}
     */
    public function setTpl($tpl)
    {
        if (empty($tpl)) {
            return $this;
        }

        if ($tpl instanceof View) {
            $this->tpl = $tpl;
        } else {
            $this->tpl = new View((string)$tpl);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTpl()
    {
        return $this->tpl;
    }

    /**
     * @return bool
     */
    public function hasTpl()
    {
        return isset($this->tpl);
    }
    /**
     * Replacement for modX::getChunk() method.
     * @param string $name
     * @param array $properties
     * @return false|string
     * @throws SmartyException
     */
    public function getChunk($name, array $properties = [])
    {
        $name = trim($name);
        if (empty($name)) {
            return '';
        }

        if (strpos($name, '@INLINE') === 0) {
            $content = preg_replace('#^@[A-Z]+:?\s+#', '', $name);
            $name = 'INLINE_' . md5($content);
            if (!$chunk = $this->chunkRepository->get($name)) {
                $chunk = $this->modx->newObject('modChunk', [
                    'id' => 0,
                    'name' => $name,
                    'content' => $content,
                ]);
            }
            //$this->modx->sourceCache['modChunk'][$name] = ['fields' => $chunk->toArray(), 'policies' => []];
        } else {
            $propertySet = '';
            if (strpos($name, '@') !== false) {
                list($name, $propertySet) = explode('@', $name, 2);
            }
            if (!$chunk = $this->chunkRepository->get($name)) {
                /** @var \modChunk $chunk */
                $chunk = $this->getElement('modChunk', $name);
                if (is_null($chunk)) {
                    $chunk = $this->modx->newObject('modChunk', [
                        'id' => 0,
                        'name' => $name,
                        'content' => '',
                    ]);
                }
                if ($chunk->id && !empty($propertySet)) {
                    //TODO: store $propertySet in the cache
                    $chunk->set('name', $propertySet ? "{$name}@{$propertySet}" : $name);
                }
            }
            //TODO: this code uses MODX parser
            $properties = $chunk->getProperties($properties);
            $content = $chunk->get('content');
        }
        $properties = $this->processProperties($properties);
        $this->chunkRepository->add($name, $chunk);

        return $this->parse($content, $properties);
    }

    /**
     * Get a modElement instance taking advantage of the modX::$sourceCache.
     *
     * @param string $class The modElement derivative class to load.
     * @param string $name An element name or raw tagName to identify the modElement instance.
     * @return modElement|null An instance of the specified modElement derivative class.
     */
    public function getElement(string $class, string $name)
    {
        if (array_key_exists($class, $this->modx->sourceCache) && array_key_exists($name, $this->modx->sourceCache[$class])) {
            /** @var modElement $element */
            $element = $this->modx->newObject($class);
            $element->fromArray($this->modx->sourceCache[$class][$name]['fields'], '', true, true);
            $element->setPolicies($this->modx->sourceCache[$class][$name]['policies']);

            if (!empty($this->modx->sourceCache[$class][$name]['source']) && !empty($this->modx->sourceCache[$class][$name]['source']['class_key'])) {
                $sourceClassKey = $this->modx->sourceCache[$class][$name]['source']['class_key'];
                $this->modx->loadClass('sources.modMediaSource');
                /* @var \modMediaSource $source */
                $source = $this->modx->newObject($sourceClassKey);
                $source->fromArray($this->modx->sourceCache[$class][$name]['source'], '', true, true);
                $element->addOne($source, 'Source');
            }
        } else {
            /** @var modElement $element */
            $element = $this->modx->getObjectGraph($class, ['Source' => []], ['name' => $name], true);
            if ($element && array_key_exists($class, $this->modx->sourceCache)) {
                $this->modx->sourceCache[$class][$name] = [
                    'fields' => $element->toArray(),
                    'policies' => $element->getPolicies(),
                    'source' => $element->Source ? $element->Source->toArray() : [],
                ];
            }
        }

        return $element;
    }

    /**
     * Replacement for modX::snippet() method.
     * @param string $name
     * @param array $properties
     * @return bool|mixed|string
     */
    public function runSnippet($name, array $properties = [])
    {
        $name = trim($name);
        if (empty($name)) {
            return '';
        }

        $propertySet = '';
        if (strpos($name, '@') !== false) {
            list($name, $propertySet) = explode('@', $name, 2);
        }
        if (!$snippet = $this->snippetRepository->get($name)) {
            /** @var \modSnippet $snippet */
            $snippet = $this->getElement('modSnippet', $name);
            if (is_null($snippet)) {
                $snippet = $this->modx->newObject('modSnippet', [
                    'id' => 0,
                    'name' => $name,
                    'snippet' => 'return;',
                ]);
            }
            if ($snippet->id && !empty($propertySet)) {
                //TODO: store $propertySet in the cache
                $snippet->set('name', "$name@$propertySet");
            }
        }
        //$properties = $this->processProperties($properties);
        $this->snippetRepository->add($name, $snippet);
        //TODO: сделать отдельный механизм без парсера MODX.
        $snippet->_cacheable = false;
        $snippet->_processed = false;
        $snippet->_propertyString = '';
        $snippet->_tag = '';

        return $snippet->process($properties);
    }

    /**
     * Parse element properties.
     * @param array $properties
     * @return array
     * @throws SmartyException
     */
    protected function processProperties(array $properties = []): array
    {
        foreach ($properties as $name => $property) {
            if (strpos($property, $this->left_delimiter) !== false) {
                $properties[$name] = $this->parse($property);
            }
        }

        return $properties;
    }

    /**
     * Clear cache.
     * @param array $targets
     * @return $this
     */
    public function refresh($targets = [])
    {
        $targets = $targets ?: ['cache', 'compiled', 'config'];
        $targets = (array)$targets;

        if (in_array('cache', $targets)) {
            $this->clearAllCache();
        }
        if (in_array('compiled', $targets)) {
            $this->clearCompiledTemplate();
        }
        if (in_array('config', $targets)) {
            $this->clearConfig();
        }

        return $this;
    }


    /**
     * Display a template by echoing the output of a Smarty::fetch().
     *
     * @param string|object $template the resource handle of the template file or template object
     * @param mixed $cache_id cache id to be used with this template
     * @param mixed $compile_id compile id to be used with this template
     * @param object $parent next higher level of Smarty variables
     */
    public function display($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL) {
        echo $this->fetch($template, $cache_id, $compile_id, $parent);
    }
}
