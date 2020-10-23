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

    return $modx->makeUrl($id, $context, $args, $scheme, $options);
}