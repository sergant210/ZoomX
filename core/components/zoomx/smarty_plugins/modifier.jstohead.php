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
function smarty_modifier_jstohead($src, $plaintext = false)
{
    global $modx;

    $modx->regClientStartupScript($src, $plaintext);
}