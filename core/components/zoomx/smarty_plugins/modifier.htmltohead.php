<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.htmltohead.php
 * Type: modifier
 * Name: htmltohead
 * Description: Register html block to the head section of the page.
 * -------------------------------------------------------------
 */
function smarty_modifier_cssToHead($src)
{
    global $modx;

    $modx->regClientStartupHTMLBlock($src);
}