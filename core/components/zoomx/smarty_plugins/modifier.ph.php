<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File: modifier.ph.php
 * Type: modifier
 * Name: ph
 * Description: Get a MODX placeholder.
 * -------------------------------------------------------------
 */
function smarty_modifier_ph($name)
{
    global $modx;

    return $modx->getPlaceholder($name);
}