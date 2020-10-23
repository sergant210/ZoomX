<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.htmltobottom.php
 * Type: modifier
 * Name: htmltobottom
 * Description: Register a html block to the end of the page.
 * -------------------------------------------------------------
 */
function smarty_modifier_htmlToBottom($src)
{
    global $modx;

    $modx->regClientHTMLBlock($src);
}