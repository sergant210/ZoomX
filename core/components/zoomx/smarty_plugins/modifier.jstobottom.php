<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.jstobottom.php
 * Type: modifier
 * Name: jstobottom
 * Description: Register js to the end of the page.
 * -------------------------------------------------------------
 */
function smarty_modifier_jstobottom($src, $plaintext = false)
{
    global $modx;

    $modx->regClientScript($src, $plaintext);
}