<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.csstohead.php
 * Type: modifier
 * Name: csstohead
 * Description: Register CSS to be injected inside the HEAD tag of a resource.
 * -------------------------------------------------------------
 */
function smarty_modifier_csstohead($src, $media = null)
{
    global $modx;

    $modx->regClientCSS($src, $media);
}