<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.markdown.php
 * Type: modifier
 * Name: markdown
 * Description: Convert content from markdown to HTML.
 * -------------------------------------------------------------
 */
function smarty_modifier_markdown($content, $secure = false)
{
    return parserx()->markdown($content, $secure);
}