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
    $zoomx = zoomx();
    if (!isset($zoomx->parsedown)) {
        $zoomx->set('parsedown', new \Parsedown());
    }
    $parsedown = zoomx('parsedown');
    $parsedown->setSafeMode($secure);

    return $parsedown->text($content);
}