<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.url.php
 * Type: modifier
 * Name: url
 * Description:  Generates a URL representing a specified resource.
 * -------------------------------------------------------------
 */
function smarty_modifier_url($id, $context = '', $args = [], $scheme = -1, array $options = [])
{
    global $modx;

    if (is_array($context)) {
        extract($context, EXTR_OVERWRITE);
        $context = is_string($context) ? $context : '';
    }

    return $modx->makeUrl($id, $context, $args, $scheme, $options);
}