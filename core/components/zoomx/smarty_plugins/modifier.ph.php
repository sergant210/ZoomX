<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.pls.php
 * Type: modifier
 * Name: pls
 * Description: Get a MODX placeholder.
 * -------------------------------------------------------------
 */
function smarty_modifier_ph($name)
{
    global $modx;

    return $modx->getPlaceholder($name);
}