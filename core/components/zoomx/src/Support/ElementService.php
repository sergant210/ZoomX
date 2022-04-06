<?php

namespace Zoomx\Support;

use modX, xPDO;
use modElement;
use modNamespace;
use SmartyException;
use ReflectionException;

final class ElementService
{
    /** @var modX $modx A reference to the modX instance */
    protected $modx;
    /** @var Repository  */
    protected $chunkRepository;
    /** @var Repository  */
    protected $snippetRepository;
    /** @var Repository  */
    protected $propertySetRepository;
    /** @var Repository File plugin objects */
    protected $pluginRepository;
    /** @var array  */
    protected $snippetPaths = [];
    /** @var array  */
    protected $config;

    /**
     * @param modX $modx A reference to the modX object
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $this->chunkRepository       = new Repository();
        $this->snippetRepository     = new Repository();
        $this->pluginRepository      = new Repository();
        $this->propertySetRepository = new Repository();

        $this->config = $config + [
                'snippet_cache_key' => 'zoomx/snippets',
                'chunk_cache_key' => 'zoomx/chunks',
            ];
        $this->processSnippetPaths();
        $this->bootstrapElements();
    }

    private function bootstrapElements()
    {
        $loader = static function ($path, $modx, $elementService) {
            $file = rtrim($path, '/') . '/elements.php';
            if (is_readable($file)) {
                require $file;
            }
        };
        # 1. Boot site file elements
        $loader(zoomx()->getConfigPath(), $this->modx, $this);
        # 2. Boot Extra's file elements
        $namespaces = $this->modx->call(modNamespace::class, 'loadCache', [$this->modx]);
        foreach ($namespaces as $namespace) {
            $loader($namespace['path'], $this->modx, $this);
        }
    }

    /**
     * Replacement for modX::getChunk() method.
     * @param string $name
     * @param array $properties
     * @param array|int $cacheOptions
     * @return string
     * @throws \ReflectionException
     */
    public function getChunk(string $name, array $properties = [], $cacheOptions = [])
    {
        $cache = !empty($cacheOptions) || $cacheOptions === 0;
        if ($cache) {
            $hash = substr(md5($name . json_encode($properties)), 0, 8);
            $cacheKey = $this->getCacheKey($name, $hash);

            if (is_numeric($cacheOptions)) {
                $cacheOptions = [xPDO::OPT_CACHE_EXPIRES => (int)$cacheOptions];
            }
            $cacheOptions = is_array($cacheOptions) ? $cacheOptions : [];
            $cacheOptions[xPDO::OPT_CACHE_KEY] = $this->config['chunk_cache_key'];
        }
        $name = trim($name);
        if (empty($name)) {
            return false;
        }
        $isFile = false;
        if (strpos($name, '@INLINE') === 0 || preg_match('~(</\w+>|{[$/]?\w+})~', $name)) {
            $content = preg_replace('#^@INLINE:?\s+#', '', $name);
            $name = 'INLINE_' . md5($content);
            if (!$chunk = $this->chunkRepository->get($name)) {
                $chunk = $this->modx->newObject('modChunk', [
                    'id' => 0,
                    'name' => $name,
                    'content' => $content,
                ]);
            }
            //$this->modx->sourceCache['modChunk'][$name] = ['fields' => $chunk->toArray(), 'policies' => []];
        } elseif (strpos($name, '@FILE') === 0 ) {
            $name = ltrim($this->sanitizePath(preg_replace('#^@FILE:?\s+#', '', $name)), '/\\');
            $isFile = true;
        } else {
            $propertySet = '';
            if (strpos($name, '@') !== false) {
                [$name, $propertySet] = explode('@', $name, 2);
            }
            if (!$chunk = $this->chunkRepository->get($name)) {
                /** @var \modChunk $chunk */
                $chunk = $this->getElement('modChunk', $name);
                if (is_null($chunk)) {
                    if (getenv("APP_ENV") !== "test") {
                        $this->modx->log(xPDO::LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_chunk_not_found', ['name' => $name]));
                    }
                    return false;
                }
            }
            if ($chunk->id > 0 && !empty($propertySet)) {
                $chunk->set('name', $propertySet ? "{$name}@{$propertySet}" : $name);
            }
            $properties = $this->getElementProperties($chunk, $properties);
            $content = $chunk->get('content');
        }

        if ($cache) {
            $cacheManager = zoomx()->getCacheManager();
            $output = $cacheManager->get($cacheKey, $cacheOptions);
            if (null !== $output) {
                return $output;
            }
        }

        if ($isFile) {
            try {
                $name = $this->getValidFilename($name);
                $output = parserx()->parse($name, $properties, $isFile);
            } catch (SmartyException $e) {
                if (getenv("APP_ENV") !== "test") {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_chunk_not_found', ['name' => $name]));
                }
            }
        } else {
            $this->chunkRepository->add($name, $chunk);
            $output = parserx()->parse($content, $properties);
        }

        if ($cache) {
            $cacheManager->set($cacheKey, $output, $cacheOptions);
        }

        return $output ?? '';
    }

    /**
     * Replacement for modX::runSnippet() method.
     * @param string $name
     * @param array $properties
     * @param array|int $cacheOptions
     * @return mixed|bool
     */
    public function runSnippet(string $name, array $properties = [], $cacheOptions = [])
    {
        $cache = !empty($cacheOptions) || $cacheOptions === 0;
        if ($cache) {
            $hash = substr(md5($name . json_encode($properties)), 0, 8);
            $cacheKey = $this->getCacheKey($name, $hash);

            if (is_numeric($cacheOptions)) {
                $cacheOptions = [xPDO::OPT_CACHE_EXPIRES => (int)$cacheOptions];
            }
            $cacheOptions = is_array($cacheOptions) ? $cacheOptions : [];
            $cacheOptions[xPDO::OPT_CACHE_KEY] = $this->config['snippet_cache_key'];
        }
        $name = trim($name);
        if (empty($name)) {
            if (getenv("APP_ENV") !== "test") {
                $this->modx->log(MODX_LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_snippet_not_found', ['name' => $name]));
            }
            return false;
        }

        $propertySet = '';
        if (strpos($name, '@') !== false) {
            [$name, $propertySet] = explode('@', $name, 2);
        }
        if (!$snippet = $this->snippetRepository->get($name)) {
            /** @var \modSnippet $snippet */
            $snippet = $this->getElement('modSnippet', $name);
            if (is_null($snippet)) {
                if (getenv("APP_ENV") !== "test") {
                    $this->modx->log(MODX_LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_snippet_not_found', ['name' => $name]));
                }
                return false;
            }
        }
        if ($snippet->id && !empty($propertySet)) {
            $snippet->set('name', "$name@$propertySet");
        }
        $this->snippetRepository->add($name, $snippet);

        $snippet->_cacheable = false;
        $snippet->_processed = false;
        $snippet->_propertyString = '';
        $snippet->_tag = '';

        if ($cache) {
            $cacheManager = zoomx()->getCacheManager();
            $output = $cacheManager->remember($cacheKey, $cacheOptions, function () use ($snippet, $properties) {
                return $snippet->process($properties);
            });
        } else {
            $output = $snippet->process($properties);
        }

        return $output;
    }

    /**
     * Executes a file like a snippet.
     * @param string $name
     * @param array $properties
     * @param array|int $cacheOptions Cache options or cache lifetime in seconds.
     * @return mixed
     */
    public function runFileSnippet(string $name, array $properties, $cacheOptions = [])
    {
        $cache = !empty($cacheOptions) || $cacheOptions === 0;
        if ($cache) {
            $hash = substr(md5($name . json_encode($properties)), 0, 8);
            $cacheKey = $this->getCacheKey($name, $hash);

            if (is_numeric($cacheOptions)) {
                $cacheOptions = [xPDO::OPT_CACHE_EXPIRES => (int)$cacheOptions];
            }
            $cacheOptions = is_array($cacheOptions) ? $cacheOptions : [];
            $cacheOptions[xPDO::OPT_CACHE_KEY] = $this->config['snippet_cache_key'];
        }
        $file = $this->findSnippetFile($name);
        if (null === $file) {
            if (getenv("APP_ENV") !== "test") {
                $this->modx->log(MODX_LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_snippet_file_not_found', ['name' => $name]));
            }
            return false;
        }
        $func = $this->getFunction();
        if ($cache) {
            $cacheManager = zoomx()->getCacheManager();
            $output = $cacheManager->remember($cacheKey, $cacheOptions, function () use ($func, $file, $properties) {
                return $func($file, $properties, $this->modx);
            });
        } else {
            $output = $func($file, $properties, $this->modx);
        }
        return $output;
    }

    /**
     * @return \Closure
     */
    private function getFunction()
    {
        return static function ($file, $scriptProperties, $modx) {
            ob_start();
            extract($scriptProperties, EXTR_SKIP);
            $output = include $file;
            $output = $output ?? '';
            $output = ob_get_length() ? ob_get_contents() . $output : $output;
            ob_end_clean();

            return $output;
        };
    }

    protected function processSnippetPaths()
    {
        if (empty($this->snippetPaths)) {
            $paths = $this->modx->getOption('zoomx_file_snippets_path', null, MODX_CORE_PATH . 'elements/snippets/');
            $paths = explode(';', $paths);
            foreach ($paths as $path) {
                $path = trim($path);
                if (is_dir($path)) {
                    $this->addSnippetPath($this->sanitizePath(rtrim($path, '/\\') . '/'));
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getSnippetPath()
    {
        return $this->snippetPaths;
    }

    /**
     * @param string $path
     * @return array
     */
    public function addSnippetPath(string $path)
    {
        if (!in_array($path, $this->snippetPaths, true)) {
            $this->snippetPaths[] = trim($path);
        }
        return $this->snippetPaths;
    }

    /**
     * @param string $path
     * @return array
     */
    public function removeSnippetPath(string $path)
    {
        if (in_array($path, $this->snippetPaths, true)) {
            $this->snippetPaths = array_diff($this->snippetPaths, [$path]);
        }

        return $this->snippetPaths;
    }

    /**
     * @param string $name
     * @return string|null
     */
    protected function findSnippetFile(string $name)
    {
        $name = $this->sanitizePath(ltrim($name, '/\\'));
        $name = pathinfo($name, PATHINFO_EXTENSION) === 'php' ? $name : "$name.php";
        foreach ($this->snippetPaths as $path) {
            $path = rtrim($path, '/') . '/';
            if (file_exists($path . $name)) {
                return $path . $name;
            }
        }

        return null;
    }

    protected function getCacheKey($name, $hash)
    {
        $ending = isset($this->modx->resource) && $this->modx->resource->id > 0 ? '_' . (string)$this->modx->resource->id : '';
        if (preg_match('#^@([A-Z]+)#', $name, $matches)) {
            switch ($matches[1]) {
            	case 'INLINE':
                    $name = 'inline';
            		break;
                case 'FILE':
                    $file = ltrim(substr($name, strlen($matches[1]) + 1), ' :');
                    $name = $this->getValidFilename(basename($file));
                    break;
            }
        }
        return preg_replace('|[^A-Za-z0-9-_.]|', '_', ltrim($name, '/\\'))  . "{$ending}_$hash";
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
        if (isset($this->modx->sourceCache[$class][$name])) {
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
            if ($element && isset($this->modx->sourceCache[$class])) {
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
     * Get the properties for this element instance for processing.
     *
     * @param modElement $element
     * @param array $properties An array of properties to apply.
     * @return array A simple array of properties which is ready to use for processing.
     * @throws \SmartyException
     */
    public function getElementProperties(modElement $element, array $properties = [])
    {
        $elementProperties = $this->prepareProperties($element->get('properties') ?? []);
        $set = $this->getElementPropertySet($element);
        if (!empty($set)) {
            $elementProperties = array_merge($elementProperties, $set);
        }
        if ($element->get('property_preprocess')) {
            $elementProperties = $this->processProperties($elementProperties);
        }

        return !empty($properties) ? array_merge($elementProperties, $properties) : $elementProperties;
    }

    /**
     * Gets a named property set related to this element instance.
     *
     * If a setName parameter is not provided, this function will attempt to
     * extract a setName from the element name using the @ symbol to delimit the
     * name of the property set.
     *
     * Here is an example of an element tag using the @ modifier to specify a property set name:
     *  {'ElementName@PropertySetName'|snippet:[
     *      'PropertyKey1'=> 'PropertyValue1',
     *      'PropertyKey2' => 'PropertyValue2',
     *  ]}
     *
     * @param modElement $element
     * @param string|null $setName An explicit property set name to search for.
     * @return array|null An array of properties or null if no set is found.
     */
    public function getElementPropertySet(modElement $element, string $setName = '')
    {
        $propertySet = [];
        $name = $element->get('name');
        if (empty($setName)) {
            [$name, $setName] = explode('@', $name, 2);
            $element->set('name', $name);
        }
        if (!empty($setName)) {
            if (!$psObj = $this->propertySetRepository->get($setName)) {
                $psObj = $this->loadPropertySet($element, $setName);
            }
            if ($psObj) {
                $propertySet = $this->prepareProperties($psObj->get('properties'));
                $this->propertySetRepository->add($setName, $psObj);
            }
        }

        return $propertySet;
    }

    /**
     * @return string
     */
    public function getTemplateDir()
    {
        $theme = str_replace(['.', '/'], '', trim($this->modx->getOption('zoomx_theme', null, 'default')));
        return $this->modx->getOption('zoomx_template_dir', null, MODX_CORE_PATH . 'components/zoomx/templates/') . ($theme ? $theme . '/' : '');
    }

    /**
     * @param string $path
     * @return string
     */
    public function sanitizePath(string $path): string
    {
        return preg_replace(["/\.*[\/|\\\]/i", "/[\/|\\\]+/i"], ['/', '/'], $path);
    }

    /**
     * @param array $classes
     * @return $this
     */
    public function registerPlugins(array $classes = [])
    {
        if (empty($classes)) {
            return $this;
        }
        if ($this->modx->getOption('zoomx_cache_event_map', null, true)) {
            $events = zoomx()->getCacheManager()->get('eventMap', 'zoomx');
        }
        if (empty($events)) {
            $events = [];
            foreach ($classes as $class) {
                if (!class_exists($class)) {
                    if (getenv("APP_ENV") !== "test") {
                        $this->modx->log(MODX_LOG_LEVEL_ERROR, "Plugin class \"$class\" not found.");
                    }
                    continue;
                }
                foreach ($class::$events as $event => $priority) {
                    $events[$event][$class] = $priority;
                }
            }

            $priorities = !empty($events) ? $this->getPluginPriorities($events) : [];
            foreach ($events as $event => $data) {
                $events[$event] += $priorities[$event] ?? [];
                asort($events[$event]);
            }
            $cache = [];
            foreach ($events as $event => $plugins) {
                $eventMap = [];
                foreach ($plugins as $plugin => $priority) {
                    if (is_string($plugin)) {
                        $code = abs(crc32($plugin));
                        if (null === $this->modx->pluginCache[$code]) {
                            $this->createVirtualPlugin($code, $plugin);
                        }
                        $eventMap[$code] = $plugin;
                    } else {
                        $eventMap[$plugin] = $this->modx->eventMap[$event][$plugin];
                    }
                }
                $this->modx->eventMap[$event] = $eventMap;
                $cache[$event] = $eventMap;
            }
            if ($this->modx->getOption('zoomx_cache_event_map', null, true)) {
                zoomx()->getCacheManager()->set('eventMap', $cache, 'zoomx');
            }
        } else {
            $customPlugin = [];
            foreach ($events as $event => $plugins) {
                $eventMap = [];
                foreach ($plugins as $pluginId => $data) {
                    if (null === $this->modx->pluginCache[$pluginId]) {
                        $this->createVirtualPlugin($pluginId, $data);
                        $customPlugin[$pluginId] = true;
                    }
                    $eventMap[$pluginId] = $customPlugin[$pluginId] !== null ? $data : $this->modx->eventMap[$event][$pluginId];
                }
                $this->modx->eventMap[$event] = $eventMap;
            }
        }

        return $this;
    }

    protected function createVirtualPlugin($code, $name)
    {
        $plugin = [
            'id' => $code,
            'source' => 1,
            'property_preprocess' => false,
            'name' => $name,
            'description' => '',
            'editor_type' => 0,
            'category' => 0,
            'cache_type' => 0,
            'locked' => 0,
            'disabled' => false,
            'properties' => null,
            'moduleguid' => '',
            'static' => 0,
            'static_file' => '',
        ];
        $plugin['plugincode'] = '
    # Class ' . $name . '
    /** @var array $scriptProperties */
    /** @var modX $modx */
    
    zoomx("elementService")->handlePlugin($this->name, $modx->event->name, $scriptProperties);
    ';
        $this->modx->pluginCache[$code] = $plugin;
    }

    /**
     * @param string $name
     * @param string $event
     * @param array $properties
     */
    public function handlePlugin($name, $event, array $properties = [])
    {
        if ($this->pluginRepository->has($name)) {
            $plugin = $this->pluginRepository->get($name);
        } else {
            $plugin = new $name($this->modx);
            $this->pluginRepository->add($name, $plugin);
        }
        if (!$plugin->isDisabled()) {
            $plugin->$event($properties);
        }
    }

    /**
     * Get or set a config key.
     * @param string|array $key
     * @param null|mixed $default
     * @return mixed|self
     */
    public function config($key, $default = null)
    {
        if (is_string($key)) {
            return $this->config[$key] ?? $default;
        }
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->config[$k] = $v;
            }
        }
        return $this;
    }
    /**
     * @param modElement $element
     * @param string $name
     * @return \modPropertySet|object|null
     */
    private function loadPropertySet(modElement $element, string $name)
    {
        $obj = $this->modx->getObjectGraph('modPropertySet', '{"Elements":{}}', [
            'Elements.element' => $element->id,
            'Elements.element_class' => $element->_class,
            'modPropertySet.name' => $name
        ]);

        return $obj;
    }

    /**
     * Prepare element properties to a simple associative array.
     *
     * @param array $elementProperties An array of the element properties.
     * @return array An associative array of property values prepared from the array definition.
     */
    protected function prepareProperties(array $elementProperties): array
    {
        $properties = [];
        foreach ($elementProperties as $property => $value) {
            if (is_array($value) && isset($value['value'])) {
                $properties[$property] = $value['value'];
            } else {
                $properties[$property] = $value;
            }
        }

        return $properties;
    }

    /**
     * Parse element properties.
     * @param array $properties
     * @return array
     * @throws SmartyException
     */
    protected function processProperties(array $properties = []): array
    {
        foreach ($properties as $name => $value) {
            if (strpos($value, $this->left_delimiter) !== false) {
                $properties[$name] = parserx()->parse($value);
            }
        }

        return $properties;
    }

    /**
     * @param array $events
     * @return array
     */
    private function getPluginPriorities(array $events): array
    {
        $query = $this->modx->newQuery('modPluginEvent');
        $query->setClassAlias('Event');
        $query->select('Event.pluginid,Event.event,Event.priority');
        $query->innerJoin('modPlugin', 'Plugin');
        $query->where([
            'Plugin.disabled' => 0,
            'Event.event:IN' => array_keys($events),
        ]);
        $query->sortby('Event.event,Event.priority', 'DESC');

        if ($query->prepare() && $query->stmt->execute()) {
            while ($row = $query->stmt->fetch(\PDO::FETCH_ASSOC)) {
                $priorities[$row['event']][$row['pluginid']] = $row['priority'];
            }
        }
        return $priorities ?? [];
    }

    /**
     * @param $name
     * @return mixed|string
     */
    private function getValidFilename($name)
    {
        $ext = zoomx('modx')->getOption('zoomx_template_extension', null, 'tpl');
        if ($ext !== pathinfo($name, PATHINFO_EXTENSION)) {
            $name .= ".$ext";
        }

        return $name;
    }
}