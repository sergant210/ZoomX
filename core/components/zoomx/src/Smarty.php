<?php
namespace Zoomx;

use modResource;
use modTemplate;
use Smarty as BaseSmarty;
use modX;

class Smarty extends BaseSmarty implements Contracts\ParserInterface
{
    /** @var modX $modx A reference to the modX instance */
    protected $modx;
    /** @var Service  */
    protected $zoomService;
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

        $corePath = dirname(__DIR__) . '/';
        $cachePath = $modx->getCachePath();
        $theme = str_replace(['.', '/'], '', trim($modx->getOption('zoomx_theme', null, 'default')));
        $this->addTemplateDir($modx->getOption('zoomx_template_dir', null, $corePath . 'templates/') . ($theme ? $theme . '/' : ''));
        $this->cache_dir = $cachePath . ltrim($modx->getOption('zoomx_smarty_cache_dir', null, 'zoomx/smarty/cache/'), '/');
        $this->compile_dir = $cachePath . ltrim($modx->getOption('zoomx_smarty_compile_dir', null, 'zoomx/smarty/compiled/'), '/');
        $this->setConfigDir($modx->getOption('zoomx_smarty_config_dir', null, $corePath . 'config/'));

        // Set caching mode
        $this->caching = $modx->getOption('cache_resource', null, true)
            ? $modx->getOption('zoomx_caching', null, Smarty::CACHING_LIFETIME_CURRENT)
            : BaseSmarty::CACHING_OFF;
        $this->cache_lifetime = (int)$modx->getOption('cache_resource_expires', null, 0);
        $this->cache_lifetime = $this->cache_lifetime > 0 ? $this->cache_lifetime : -1;

        // Set plugin directories
        $pluginsDir = [
            $corePath . 'smarty/plugins/',
        ];
        $customPluginDir = $modx->getOption('zoomx_smarty_custom_plugin_dir', null, '{core_path}components/zoomx/smarty/custom_plugins/');
        if (!empty($customPluginDir)) {
            $pluginsDir[] = $customPluginDir;
        }
        $this->addPluginsDir($pluginsDir);

        // Enable security
        if ($modx->getOption('zoomx_smarty_security_enable', null, false)) {
            $securityClass = $this->getSecurityClass($corePath);
            empty($securityClass) or $this->enableSecurity($securityClass);
        }

        // Set prefilters
        $this->loadPrefilters();

        // Register shorthand modifiers
        $this->registerShortModifiers($corePath . 'smarty/plugins/');

        // Get available $modx object in the templates
        if ($modx->getOption('zoomx_include_modx', null, true)) {
            $this->assign('modx', $modx, true);
            $this->assign('zoomx', $zoomService, true);
        }

        // Register default modifier handler for snippet.
        $this->registerDefaultPluginHandler([$this, 'loadDefaultPluginHandler']);
    }

    protected function getSecurityClass($corePath)
    {
        if ($securityClass = $this->modx->getOption('zoomx_smarty_security_class', null, '')) {
            $FQN = $corePath . "smarty/security/$securityClass.php";
            if (!file_exists($FQN)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, "Class $securityClass not found.");
            } else {
                include $FQN;
            }
        }
        return $securityClass ?? '';
    }

    protected function loadPrefilters()
    {
        $preFilters = ['scripts', 'ignore', 'include'];
        if ($this->modx->getOption('zoomx_modx_tag_syntax', null, true)) {
            $preFilters[] = 'modxtags';
        }

        foreach ($preFilters as $filter) {
            $this->loadFilter('pre', $filter);
        }
    }

    protected function registerShortModifiers($pluginsDir)
    {
        $modifiers = [
            'css' => 'csstohead',
            'js' => 'jstobottom',
            'html' => 'htmltobottom',
        ];
        foreach ($modifiers as $shortName => $modifier) {
            require_once $pluginsDir . "modifier.$modifier.php";
            $this->registerPlugin("modifier", $shortName, "smarty_modifier_$modifier");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function process(modResource $resource = null): string
    {
        $output = '';

        if (!$this->hasTpl()) {
            return $output;
        }
        if ($this->templateExists($this->tpl->name)) {
            if (isset($resource)) {
                $this->setCaching($this->caching && $resource->cacheable);
                $cacheId = "doc_" . $resource->id;
            } else {
                $this->setCaching($this->caching);
                $cacheId = null;
            }

            if ($this->tpl->hasData()) {
                $this->assign($this->tpl->data);
            }
            $output = $this->fetch($this->tpl->name, $cacheId);
            $output = $output === false ? '' : $output;

        } elseif ($this->tpl->hasContent()) {
            $output = $this->tpl->content;
        } else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_template_not_found', ['name' => $this->tpl]));
        }
        if (isset($resource)) {
            $resource->setProcessed(true);
        }

        return $output;
    }

    /**
     * Parse the MODX template of the current resource.
     * @param modResource $resource
     * @return string
     * @throws \SmartyException
     */
    public function processResource($resource)
    {
        if (!$resource->_processed) {
            $resource->_output = $content = '';
            if (empty($resource->_content)) {
                /** @var modTemplate $baseElement */
                $baseElement = $resource->getOne('Template');
                $resource->_content = isset($baseElement) ? $baseElement->getContent() : $resource->getContent();
            }
            if (!empty($resource->_content)) {
                $content = $this->parse($resource->_content);
            }
            $resource->setProcessed(true);
        } else {
            $content = $resource->_output;
        }
        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function parse($string, array $properties = [], $isFile = false)
    {
        if (empty($string)) {
            return '';
        }
        if (!$isFile) {
            $string = 'string:' . $string;
        }
        $tmpl = $this->createTemplate($string);
        if (!empty($properties)) {
            $tmpl->assign($properties);
        }
        $tmpl->caching = BaseSmarty::CACHING_OFF;

        return $tmpl->fetch();
    }

    /**
     * {@inheritDoc}
     */
    public function setTpl($tpl, array $data = [])
    {
        if (empty($tpl)) {
            return $this;
        }

        if ($tpl instanceof View) {
            $this->tpl = $tpl;
        } else {
            $this->tpl = $this->zoomService->getView($tpl, $data);
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
     * Default Plugin Handler
     *
     * Called when Smarty encounters an undefined tag during compilation
     *
     * @param string                     $name      name of the undefined tag
     * @param string                     $type     tag type (e.g. Smarty::PLUGIN_FUNCTION, Smarty::PLUGIN_BLOCK,
    Smarty::PLUGIN_COMPILER, Smarty::PLUGIN_MODIFIER, Smarty::PLUGIN_MODIFIERCOMPILER)
     * @param \Smarty_Internal_Template  $template     template object
     * @param string|array               &$callback    returned function name
     * @param string                     &$script      optional returned script filepath if function is external
     * @param bool                       &$cacheable    true by default, set to false if plugin is not cachable (Smarty >= 3.1.8)
     * @return bool                      true if successful
     */
    public function loadDefaultPluginHandler($name, $type, $template, &$callback, &$script, &$cacheable)
    {
        if ($type === BaseSmarty::PLUGIN_MODIFIER) {
            $callback = [__CLASS__, 'run_' . $name];
            return true;
        }
        return false;
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        [$input, $options] = $params;

        if (strpos($method, 'run_') !== 0) {
            return $input;
        }
        $snippetName = preg_replace('|^run_|', '', $method);
        $result = zoomx()->runSnippet($snippetName, [
            'input' => $input,
            'options' => $options,
        ]);

        return $result === false ? $input : $result;
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
