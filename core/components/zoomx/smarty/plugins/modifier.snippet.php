<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.snippet.php
 * Type: modifier
 * Name: snippet
 * Description: Run a MODX snippet or a file snippet.
 * -------------------------------------------------------------
 */
function smarty_modifier_snippet($name, $properties = [], $cache_lifetime = null)
{
    $isFile = false;
    if (strpos($name, '@FILE') === 0 ) {
        $name = ltrim(preg_replace('#^@FILE:?\s+#', '', $name));
        $isFile = true;
    }
    if (is_array($cache_lifetime) && isset($cache_lifetime['cache_lifetime'])) {
        $cache_lifetime['cache_expires'] = $cache_lifetime['cache_lifetime'];
        unset($cache_lifetime['cache_lifetime']);
    }

    /** @var ZoomX\Support\ElementService $elService */
    $elService = zoomx('elementService');
    return $isFile ? $elService->runFileSnippet($name, $properties, $cache_lifetime) : $elService->runSnippet($name, $properties, $cache_lifetime);
}