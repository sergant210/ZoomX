<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     prefilter.modxtags.php
 * Type:     prefilter
 * Name:     modxtags
 * Purpose:  Handle modx tags.
 * -------------------------------------------------------------
 */
function smarty_prefilter_modxtags($source, Smarty_Internal_Template $template)
{
    $patterns = [
        'resource' => "~{'(\*)(\w+)'(.*)}~",
        'lexicon' => "~{'(%)(\w+)'(.*)}~",
        'config' => "~{'(\+){2}(\w+)'(.*)}~",
        'url' => "/{'(~)(\d+)'(.*)}/",
    ];

    foreach ($patterns as $modifier => $pattern) {
        $source = preg_replace($pattern, "{'$2'|" . $modifier . "$3}", $source);
    }

    return $source;
}