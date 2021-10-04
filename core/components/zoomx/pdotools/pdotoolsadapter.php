<?php

trait pdoToolsAdapter
{
    /**
     * Process and return the output from a Chunk by name.
     *
     * @param string $name The name of the chunk.
     * @param array $properties An associative array of properties to process the Chunk with, treated as placeholders within the scope of the Element.
     * @param boolean $fastMode If false, all MODX tags in chunk will be processed.
     *
     * @return mixed The processed output of the Chunk.
     */
    public function getChunk($name = '', array $properties = array(), $fastMode = false)
    {

        $properties = $this->prepareRow($properties);
        $name = trim($name);

        /** @var array $data */
        if (!empty($name)) {
            $data = $this->_loadElement($name, 'modChunk', $properties);
        }
        if (empty($name) || empty($data) || !($data['object'] instanceof modElement)) {
            return !empty($properties)
                ? str_replace(array('[', ']', '`'), array('&#91;', '&#93;', '&#96;'),
                              htmlentities(print_r($properties, true), ENT_QUOTES, 'UTF-8'))
                : '';
        }

        $properties = array_merge($data['properties'], $properties);

        return parserx()->parse($data['content'], $properties);
    }


    /**
     * Parse a chunk using an associative array of replacement variables.
     *
     * @param string $name The name of the chunk.
     * @param array $properties An array of properties to replace in the chunk.
     * @param string $prefix The placeholder prefix, defaults to [[+.
     * @param string $suffix The placeholder suffix, defaults to ]].
     *
     * @return string The processed chunk with the placeholders replaced.
     */
    public function parseChunk($name = '', array $properties = array(), $prefix = '[[+', $suffix = ']]')
    {
        $properties = $this->prepareRow($properties);
        $name = trim($name);

        /** @var array $chunk */
        if (!empty($name)) {
            $chunk = $this->_loadElement($name, 'modChunk', $properties);
        }
        if (empty($name) || empty($chunk['content'])) {
            return !empty($properties)
                ? str_replace(array('[', ']', '`'), array('&#91;', '&#93;', '&#96;'),
                              htmlentities(print_r($properties, true), ENT_QUOTES, 'UTF-8'))
                : '';
        }

        $properties = array_merge($chunk['properties'], $properties);

        return parserx()->parse($chunk['content'], $properties);
    }

    /**
     * Handle dynamic calls.
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return method_exists($this->cacheManager, $method) ? call_user_func_array(array($this->cacheManager, $method), $parameters) : null;
    }
}