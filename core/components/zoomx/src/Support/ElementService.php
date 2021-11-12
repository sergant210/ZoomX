<?php

namespace Zoomx\Support;

use modX, xPDO;
use modElement;
use SmartyException;
use ReflectionException;

class ElementService
{
    /** @var modX $modx A reference to the modX instance */
    protected $modx;
    /** @var Repository  */
    protected $chunkRepository;
    /** @var Repository  */
    protected $snippetRepository;
    /** @var Repository  */
    protected $propertySetRepository;
    protected $snippetPaths = [];

    /**
     * @param modX $modx A reference to the modX object
     */
    public function __construct(modX $modx)
    {
        $this->modx = $modx;
        $this->chunkRepository       = new Repository();
        $this->snippetRepository     = new Repository();
        $this->propertySetRepository = new Repository();
    }

    /**
     * Replacement for modX::getChunk() method.
     * @param string $name
     * @param array $properties
     * @return string
     * @throws SmartyException|ReflectionException
     */
    public function getChunk(string $name, array $properties = [])
    {
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
                if ($chunk->id > 0 && !empty($propertySet)) {
                    $chunk->set('name', $propertySet ? "{$name}@{$propertySet}" : $name);
                }
            }
            $properties = $this->getElementProperties($chunk, $properties);
            $content = $chunk->get('content');
        }
        $output = '';
        if ($isFile) {
            try {
                $output = parserx()->parse($name, $properties, $isFile);
            } catch (SmartyException $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_chunk_not_found', ['name' => $name]));
            }
        } else {
            $this->chunkRepository->add($name, $chunk);
            $output = parserx()->parse($content, $properties);
        }

        return $output;
    }

    /**
     * Replacement for modX::runSnippet() method.
     * @param string $name
     * @param array $properties
     * @return mixed|bool
     */
    public function runSnippet(string $name, array $properties = [])
    {
        $name = trim($name);
        if (empty($name)) {
            if (getenv("APP_ENV") !== "test") {
                $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_snippet_not_found', ['name' => $name]));
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
                    $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_snippet_not_found', ['name' => $name]));
                }
                return false;
            }
            if ($snippet->id && !empty($propertySet)) {
                $snippet->set('name', "$name@$propertySet");
            }
        }
        $this->snippetRepository->add($name, $snippet);
        //TODO: exclude the MODX parser.
        $snippet->_cacheable = false;
        $snippet->_processed = false;
        $snippet->_propertyString = '';
        $snippet->_tag = '';

        return $snippet->process($properties);
    }

    /**
     * Executes a file like a snippet.
     * @param string $name
     * @param array $scriptProperties
     * @return mixed
     */
    public function runFileSnippet(string $name, array $scriptProperties)
    {
        $file = $this->findFile($name);
        if (null === $file) {
            if (getenv("APP_ENV") !== "test") {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $this->modx->lexicon('zoomx_snippet_file_not_found', ['name' => $name]));
            }
            return false;
        }

        return call_user_func(
            static function ($file, $scriptProperties, $modx) {
                ob_start();
                extract($scriptProperties, EXTR_SKIP);
                $output = include $file;
                $output = $output ?? '';
                $output = ob_get_length() ? ob_get_contents() . $output : $output;
                ob_end_clean();

                return $output;
            },
            $file,
            $scriptProperties,
            $this->modx
        );
    }

    /**
     * @param string $name
     * @return string|null
     */
    protected function findFile(string $name)
    {
        if (empty($this->snippetPaths)) {
            $paths = $this->modx->getOption('zoomx_file_snippets_path', null, MODX_CORE_PATH . 'elements/snippets/');
            $paths = explode(';', $paths);
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $this->addSnippetPath($this->sanitizePath(rtrim($path, '/\\') . '/'));
                }
            }
        }
        $name = $this->sanitizePath(ltrim($name, '/\\'));
        $name = pathinfo($name, PATHINFO_EXTENSION) === 'php' ? $name : "$name.php";
        foreach ($this->snippetPaths as $path) {
            if (file_exists($path . $name)) {
                return $path . $name;
            }
        }

        return null;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function addSnippetPath(string $path)
    {
        $this->snippetPaths[] = $path;
        return $this;
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
}