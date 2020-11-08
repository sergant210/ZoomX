<?php
namespace Zoomx;

use modResource;
use SmartyException;

interface ParserInterface
{
    /**
     * @param modResource|null $resource
     * @return string
     * @throws SmartyException
     */
    public function process(modResource $resource = null);

    /**
     * @param string $string Content for parsing
     * @param array $properties
     * @return false|string
     * @throws SmartyException
     */
    public function parse($string, array $properties = []);

    /**
     * Clear cache.
     * @param array $targets
     */
    public function refresh($targets = []);

    /**
     * @param View|string $tpl
     * @return self
     */
    public function setTpl($tpl);
    /**
     * @return View
     */
    public function getTpl();

    /**
     * @return bool
     */
    public function hasTpl();

}