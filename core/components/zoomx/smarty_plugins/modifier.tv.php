<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.tv.php
 * Type: modifier
 * Name: tv
 * Description: Output a TV value.
 * -------------------------------------------------------------
 */
function smarty_modifier_tv($name)
{
    global $modx;

    if (isset($modx->resource, $modx->resource->{$name})) {
        return $modx->resource->getTVValue($name);
    }

    return null;
}