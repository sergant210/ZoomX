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
function smarty_modifier_snippet($name, $properties = [])
{
    $isFile = false;
    if (strpos($name, '@FILE') === 0 ) {
        $name = ltrim(preg_replace('#^@FILE:?\s+#', '', $name));
        $isFile = true;
    }
    $elService = zoomx('elementService');
    return $isFile ? $elService->runFileSnippet($name, $properties) : $elService->runSnippet($name, $properties);
}