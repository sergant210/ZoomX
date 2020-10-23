<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.jstohead.php
 * Type: modifier
 * Name: jstohead
 * Description: Register js to the head of the page.
 * -------------------------------------------------------------
 */
function smarty_modifier_cssToHead($src, $plaintext = false)
{
    global $modx;

    $modx->regClientStartupScript($src, $plaintext);
}