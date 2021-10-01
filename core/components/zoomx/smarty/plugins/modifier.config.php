<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.config.php
 * Type: modifier
 * Name: config
 * Description: Output the resource TV value.
 * -------------------------------------------------------------
 */
function smarty_modifier_config($key, $default = '')
{
    global $modx;

    return $modx->getOption($key, null, $default);
}